<?php

class Model
{
	
	private $mongo;
	private $db;
	
	private $url;
	private $config;
	
	// Collections
	private $content;
	private $log;
	
	public function __construct($config)
	{
		$this->config = $config;
		
		// Check hostname
		if ( ! isset($config['database']['host']) || empty($config['database']['host']) ) {
			$config['database']['host'] = 'localhost';
		}
		
		// Check port
		if ( ! isset($config['database']['port']) || empty($config['database']['port']) ) {
			$config['database']['port'] = 27017;
		}
		
		// Connect
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
	 * Find content near given longitude, latitude
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
				'$maxDistance' => (float)$this->config['putaspot']['explore_distance']),
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
	 * Find content within given box defined by two corners
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
		$box = array((float)$lat1, (float)$lng1, (float)$lat2, (float)$lng2);
		$cursor = $this->content->find(array(
			'loc' => array(
				'$within' 		=> $box),
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
	 * Retrieve a single piece of content near given longitude, latitude
	 *
	 * @param string $id		Content ID
	 * @param string $lng 		Longitude
	 * @param string $lat 		Latitude
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
			return $piece;
		}
		return array('error' => 'TOO_FAR_AWAY');
	}
	
	/**
	 * Add content and perform metadata lookup
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
		
		// Log event and check if user is close enough
		// to be allowed to add content
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
		if ( ! preg_match('/^[a-z]*:\/\//', $content['url']) && ! strstr($content['url'], 'spotify:')) {
			$content['url'] = 'http://' . $content['url'];
		}
		$this->url->setUrl($content['url']);
		$this->url->normalize();
		$service = $this->url->getService();
		$content['url'] = $this->url->getUrl();
		
		// Check for suitable meta-data class
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
		
		// Mark that content was successfully added
		$event['content_id'] = $content['_id'];
		$event['added']	 = 1;
		$this->log->update(array('_id' => $eventID), $event); 
		
		return $content;
	}
	
	
}