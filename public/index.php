<?php
/**
 * @file
 *
 * Index of Putaspot: the revolutionary new thing that has to do with places
 * and stuff.
 */

// Introduce a web root constant for better relative path handling 
define('WEB_ROOT', dirname(__FILE__));

require WEB_ROOT . '/../bootstrap.php';
$app = new Slim();

// Get the standard webpage
$app->get('/', function () {
    View::renderPage('index', array());
});

// Get the mobile version
$app->get('/m', function () {
    View::render('mobile', array());
});

// API delivers some sweet JSON of nearby... stuff
$app->get('/near.json', function () {
	global $model;
	global $_GET;
	echo json_encode($model->near($_GET['lng'], $_GET['lat']));
});

// API delivers some sweet JSON of things in a box
$app->get('/within.json', function () {
	global $model;
	global $_GET;
	echo json_encode($model->within($_GET['lng1'], $_GET['lat1'], $_GET['lng2'], $_GET['lat2']));
});

// API delivers some sweet JSON of specific... stuff
$app->get('/single.json', function () {
	global $model;
	global $_GET;
	echo json_encode($model->single($_GET['id'], $_GET['lng'], $_GET['lat']));
});

// This is what's called when you PUT a SPOT
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
