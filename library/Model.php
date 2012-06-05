<?php

class Model
{
	
	private $mongo;
	private $db;
	
	private $url;
	private $config;
	
	// Collections
	private $content;
	
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
		$pieces = array();
		$latLng = array((float)$lat, (float)$lng);
		$cursor = $this->content->find(array(
			'loc' => array(
				'$near' => $latLng, '$maxDistance' => (float)$this->config['putaspot']['distance']),
			'expires' => array(
				'$gt' => time())
		));
		while ( $cursor->hasNext() ) {
			$piece = $cursor->getNext();
			$pieces[] = $piece;
		}
		return $pieces;
	}
	
	public function add($content)
	{
		// Add timestamps and expiration time
		$content['added'] 	= time();
		$content['expires']	= time() + (int)$this->config['putaspot']['expiration'];
		
		// JSON encoding might mess up float values
		$content['loc'][0] = (float)$content['loc'][0];
		$content['loc'][1] = (float)$content['loc'][1];
		
		// Normalize URL and retrieve service name
		$this->url->setUrl($content['url']);
		$url = $this->url->normalize();
		$service = $this->url->getService();
		
		// Check for suitable meta-data class
		$class = str_replace('.', '_', ucfirst($service));
		error_log($class); // DEBUG
		$classFileName = APPLICATION_PATH . '/library/Meta/' . $class . '.php';
		if ( file_exists($classFileName) ) {
			require_once $classFileName;
			$meta = new $class;
			$content = $meta->resolve($content);
		}

		$status = $this->content->insert($content, array('safe' => true));
		return $content;
	}
	
	
}
