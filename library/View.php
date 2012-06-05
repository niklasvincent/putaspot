<?php

class View
{
	
	public static function render($templateFileName, $data, $suffix = '.mustache')
	{
		$m = new Mustache();
		if ( defined('APPLICATION_PATH') ) {
			$templateFullFilename = APPLICATION_PATH . '/view/' . $templateFileName . $suffix;
		} else {
			$templateFileName = $templateFileName . $suffix;
		}
		if ( file_exists($templateFullFilename) ) {
			$template = @file_get_contents($templateFullFilename);
			echo $m->render($template, $data);
			return;
		}
		throw new Exception('View ' . $templateFileName . ' not found');

	}
	
	public static function header()
	{
		self::render('head', array());
	}
	
	public static function footer()
	{
		self::render('foot', array());
	}
	
	public static function renderPage($templateFileName, $data, $suffix = '.mustache')
	{
		self::header();
		self::render($templateFileName, $data, $suffix);
		self::footer();
	}
	
}