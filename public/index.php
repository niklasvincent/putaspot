<?php

require '../bootstrap.php';
$app = new Slim();

$app->get('/', function () {
    View::renderPage('index', array());
});

$app->get('/m', function () {
    View::render('mobile', array());
});

$app->get('/near.json', function () {
	global $model;
	global $_GET;
	echo json_encode($model->near($_GET['lng'], $_GET['lat']));
});

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