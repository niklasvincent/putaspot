<?php
/**
 * Data model for Putaspot
 *
 * @package putaspot
 * @author Niklas Lindblad
 */

class Model
{
	
	/**
	 * Database handlers for MongoDB.
	 *
	 * @author Niklas Lindblad
	 */
	private $mongo;
	private $db;
	
	private $url;
	private $config;
	
	/**
	 * Two MongoDB collections are used:
	 * One for content and one for logs.
	 * 
	 * @author Niklas Lindblad
	 */
	private $content;
	private $log;
	
	/**
	 * Initialize data model.
	 *
	 * @param array $config Application configuration
	 * @author Niklas Lindblad
	 */
	public function __construct($config)
	{
		$this->config = $config;
		
		// Check hostname, default to 'localhost'
		if ( ! isset($config['database']['host']) || empty($config['database']['host']) ) {
			$config['database']['host'] = 'localhost';
		}
		
		// Check port, default to 27017
		if ( ! isset($config['database']['port']) || empty($config['database']['port']) ) {
			$config['database']['port'] = 27017;
		}
		
		// Connect to the database
		$this->mongo = new Mongo($config['database']['host'] . ':' . $config['database']['port']);
		
		// Check database name
		if ( ! isset($config['database']['name']) || empty($config['database']['name']) ) {
			throw new Exception('No database name given.');
		}
		
		// Select database
		$this->db = $this->mongo->selectDB($config['database']['name']);
		
		// Initialize collections
		$this->content = new MongoCollection($this->db, 'content');
		$this->log = new MongoCollection($this->db, 'log');
		
		// Initialize URLNormalizer
		$this->url = new URLNormalizer();
	}
	
	/**
	 * Find content near given longitude, latitude.
	 *
	 * @param string $lng 		Longitude
	 * @param string $lat 		Latitude
	 * @return array $contents	Array of contents
	 * @author Niklas Lindblad
	 */
	public function near($lng, $lat)
	{
		global $_GET;
		$pieces = array();
		$latLng = array((float)$lat, (float)$lng);
		$cursor = $this->content->find(array(
			'loc' => array(
				'$near' => $latLng, 
				'$maxDistance' => (float)$this->config['putaspot']['distance']),
			'expires' => array(
				'$gt' => time())
		));
		while ( $cursor->hasNext() ) {
			$piece = $cursor->getNext();
			if ( ! isset($_GET['m']) ) {
				unset($piece['url']);
			}
			$pieces[] = $piece;
		}
		return $pieces;
	}
	
	/**
	 * Find content within given box defined by two corners.
	 *
	 * @param string $lng 		Longitude 1
	 * @param string $lat 		Latitude 1
	 * @param string $lng 		Longitude 2
	 * @param string $lat 		Latitude 2
	 * @return array $contents	Array of contents
	 * @author Niklas Lindblad
	 */
	public function within($lng1, $lat1, $lng2, $lat2)
	{
		global $_GET;
		$pieces = array();
		$box = array(array((float)$lat1, (float)$lng1), array((float)$lat2, (float)$lng2));
		$cursor = $this->content->find(array(
			'loc' => array(
				'$within' 		=> array('$box' => $box)),
			'expires' => array(
				'$gt' => time())
		));
		while ( $cursor->hasNext() ) {
			$piece = $cursor->getNext();
			if ( ! isset($_GET['m']) ) {
				unset($piece['url']);
			}
			$pieces[] = $piece;
		}
		return $pieces;
	}
	
	/**
	 * Retrieve a single piece of content near given longitude, latitude.
	 *
	 * Will return an error if the user is not close enough.
	 *
	 * Allowed distance is determined by the 'distance' configuration option.
	 *
	 * @param string $id		Content ID
	 * @param string $lng 		Longitude of user
	 * @param string $lat 		Latitude of user
	 * @return array $content	The content
	 * @author Niklas Lindblad
	 */
	public function single($id, $lng, $lat)
	{
		$id = new MongoID($id);
		$latLng = array((float)$lat, (float)$lng);
		$cursor = $this->content->find(array(
			'loc' => array(
				'$near' => $latLng, 
				'$maxDistance' => (float)$this->config['putaspot']['distance']),
			'expires' => array(
				'$gt' => time()),
			'_id' => $id));
		while ( $cursor->hasNext() ) {
			$piece = $cursor->getNext();
			
			// Increment picked counter
			$piece['picked'] = ( ! isset($piece['picked']) ) ? 1 : (int)$piece['picked'];
			$updateQuery = array('$inc' => array('picked' => 1)); 
			$this->content->update(array('_id' => $id), $updateQuery);
			
			return $piece;
		}
		return array('error' => 'TOO_FAR_AWAY');
	}
	
	/**
	 * Add content and perform metadata lookup.
	 *
	 * Will return an error if the user is not close enough.
	 *
	 * Allowed distance is determined by the 'distance' configuration option.
	 *
	 * Also sets an expiration time for the content. Determined by the
	 * configuration option 'expiration'.
	 *
	 * @param array $content 			The original content
	 * @return array $processedContent	The processed content
	 * @author Niklas Lindblad
	 */
	public function add($content)
	{
		global $_SERVER;
		
		// Add timestamps and expiration time
		$content['added'] 	= time();
		$content['expires']	= time() + (int)$this->config['putaspot']['expiration'];
		
		// JSON encoding might mess up float values
		$content['loc'][0] = (float)$content['loc'][0];
		$content['loc'][1] = (float)$content['loc'][1];
		
		/**
		 * The real trick here is to determine whether the user is close
		 * enough or not. This is done by inserting a 'log' object into
		 * MongoDB that represents the user's position and then see if
		 * the coordinates of the content the user wants to add is within
		 * the allowed range.
		 *
		 * This way, all of the geospatial calculations are done in
		 * MongoDB :-)
		 *
		 * @author Niklas Lindblad
		 */
		$event = array(
			'loc'		=> array(
				(float)$content['user_loc'][0],
				(float)$content['user_loc'][1]
			),
			'ip'		=>	$_SERVER['REMOTE_ADDR'],
			'added'		=> 	0 // Default is to deny
		);
		
		$this->log->insert($event, array('fsync' => true));
		$eventID = new MongoID($event['_id']);
		
		// Check if the user is allowed to do this
		$cursor = $this->log->find(array(
			'loc' => array(
				'$near' => $content['loc'], 
				'$maxDistance' => (float)$this->config['putaspot']['distance']),
			'_id' => $eventID)
		);
		
		if ( $cursor == null || ! $cursor->hasNext() ) {
			// Disallow
			return array('error' => 'TOO_FAR_AWAY');
		}
					
		// Normalize URL and retrieve service name
		$content['url'] = strip_tags($content['url']);
		
		/**
		 * Contains a work-around to not mess with the native Spotify URIs
		 * <spotify:track:4VRLGNMTfymoYzD4dLAsNb>
		 *
		 * TODO: Make a more generic handler for this since it might be
		 *       desirable to allow other non-standard URI formats in
		 *       the future.
		 *
		 * @author Niklas Lindblad
		 */
		if ( ! preg_match('/^[a-z]*:\/\//', $content['url']) && ! strstr($content['url'], 'spotify:')) {
			$content['url'] = 'http://' . $content['url'];
		}
		$this->url->setUrl($content['url']);
		$this->url->normalize();
		$service = $this->url->getService();
		$content['url'] = $this->url->getUrl();
		
		/**
		 * Check for a suitable meta data class to resolve the
		 * meta data for the content (so far we only know the URL).
		 *
		 * The name of the service (e.g. Spotify.com) gets transformed
		 * into Spotify_com (class name). Subdomains will be removed, so
		 * <http://open.spotify.com/track/4VRLGNMTfymoYzD4dLAsNb> will
		 * be treated as Spotify_com automatically.
		 *
		 * After the class name is determined we check if there is any
		 * file in the 'library/Meta' directory that matches.
		 *
		 * If so, we initialize a new instance of that class and use it
		 * to resolve the meta data by calling its resolve() method.
		 *
		 * This allows for a very flexible plugin system since you
		 * only need to put a class containing a proper resolve()
		 * method into 'library/Meta' and it will automatically get
		 * picked up here!
		 *
		 * @author Niklas Lindblad
		 */
		$class = str_replace('.', '_', ucfirst($service));
		$classFileName = APPLICATION_PATH . '/library/Meta/' . $class . '.php';
		if ( file_exists($classFileName) ) {
			require_once $classFileName;
			$meta = new $class;
			$content = $meta->resolve($content);
		}

		$this->content->insert($content, array(
			'safe' => true, 
			'fsync' => true)
		);
		
		/**
		 * Here the log object previously used to check
		 * whether the user was allowed to add the content
		 * or not is updated to reflect that the content
		 * has been added properly. It also adds a reference 
		 * to the created content object.
		 *
		 * This provides a complete back-log of all content
		 * and the origin of each content piece.
		 *
		 * @author Niklas Lindblad
		 */
		$event['content_id'] = $content['_id'];
		$event['added']	 = 1;
		$this->log->update(array('_id' => $eventID), $event); 
		
		return $content;
	}
	
}