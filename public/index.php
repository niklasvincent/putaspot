<?php
/**
 * Putaspot - Demo application for MongoDB geospatial features
 *
 * A project by:
 *
 * Niklas Lindblad (@nlindblad) and
 * Nicklas Nygren (@mossisen)
 *
 * @package putaspot
 * @author Niklas Lindblad
 */

/**
 * Introduce a web root constant for better relative path handling.
 */
define('WEB_ROOT', dirname(__FILE__));

require WEB_ROOT . '/../bootstrap.php';
$app = new Slim();

/**
 * Get the standard webpage.
 *
 * @author Niklas Lindblad
 */
$app->get('/', function () {
    View::renderPage('index', array());
});

/**
 * Get the mobile version.
 *
 * @author Niklas Lindblad
 */
$app->get('/m', function () {
    View::render('mobile', array());
});

/**
 * Get near by spots as JSON.
 *
 * @author Niklas Lindblad
 */
$app->get('/near.json', function () {
	global $model;
	global $_GET;
	echo json_encode($model->near($_GET['lng'], $_GET['lat']));
});

/**
 * Get spots within given area as JSON.
 *
 * @author Niklas Lindblad
 */
$app->get('/within.json', function () {
	global $model;
	global $_GET;
	echo json_encode($model->within($_GET['lng1'], $_GET['lat1'], $_GET['lng2'], $_GET['lat2']));
});

/**
 * Get information about a single spot as JSON.
 *
 * @author Niklas Lindblad
 */
$app->get('/single.json', function () {
	global $model;
	global $_GET;
	echo json_encode($model->single($_GET['id'], $_GET['lng'], $_GET['lat']));
});

/**
 * Add a spot using POST.
 *
 * @author Niklas Lindblad
 */
$app->post('/add', function () {
	$content = json_decode(file_get_contents("php://input"), true);

	if ( ! $content ) {
		throw new Exception('Unacceptable input provided.');
	}
	
	if ( ! isset($content['url']) || ! isset($content['loc']) ) {
		throw new Exception('Required fields not provided.');
	}
	
	if ( count($content['loc']) != 2 ) {
		throw new Exception('No location data provided.');
	}
	
	global $model;
	echo json_encode($model->add($content));
});

$app->run();