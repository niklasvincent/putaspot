<?php
/**
 * Bootstrap the Putaspot application
 *
 * @package putaspot
 * @author Niklas Lindblad
 */

// Define absolute paths.
define('APPLICATION_PATH', dirname(__FILE__));
define('APPLICATION_CONFIG', APPLICATION_PATH . '/config.ini');

// Include dependencies
require APPLICATION_PATH . '/library/URLNormalizer.php';
require APPLICATION_PATH . '/library/HTTP.php';
require APPLICATION_PATH . '/library/Slim/Slim.php';
require APPLICATION_PATH . '/library/Mustache/Mustache.php';
require APPLICATION_PATH . '/library/View.php';
require APPLICATION_PATH . '/library/Model.php';

// Read configuration
if ( ! file_exists(APPLICATION_CONFIG) ) {
	throw new Exception('No configuration found: ' . APPLICATION_CONFIG);
}

$config = parse_ini_file(APPLICATION_PATH . '/config.ini', true);

if ( $config === false ) {
	throw new Exception('Could not read configuration: ' . APPLICATION_CONFIG);
}

// Initialize Model
$model = new Model($config);

?>