<?php
/**
 * View for Putaspot
 *
 * All templating done using Mustache <http://mustache.github.com/> 
 *
 * @package putaspot
 * @author Niklas Lindblad
 */
class View
{
	
	/**
	 * Render given template with Mustache.
	 *
	 * @param string $templateFileName The filename of the template
	 * @param array $data 			   The data to be rendered
	 * @param string $suffix 		   Template file extension (default '.mustache')
	 * @return void
	 * @author Niklas Lindblad
	 */
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
	
	/**
	 * Render 'view/head.mustache'
	 *
	 * @return void
	 * @author Niklas Lindblad
	 */
	public static function header()
	{
		self::render('head', array());
	}
	
	/**
	 * Render 'view/foot.mustache'
	 *
	 * Will add Google Analytics tracking JS
	 * if an API key is provided in the
	 * application configuration file.
	 *
	 * @return void
	 * @author Niklas Lindblad
	 */
	public static function footer()
	{
		global $config;
		
		// Check for Google Analytics
		if ( isset($config['google_analytics']['api_key']) && strstr($config['google_analytics']['api_key'], 'UA') ) {
			self::render('google_analytics', 
				array('google_analytics_api_key' => $config['google_analytics']['api_key']));
		}
		
		self::render('foot', array());
	}
	
	/**
	 * Render the header, then given template and finally the footer.
	 *
	 * @param string $templateFileName The filename of the template
	 * @param array $data 			   The data to be rendered
	 * @param string $suffix 		   Template file extension (default '.mustache')
	 * @return void
	 * @author Niklas Lindblad
	 */
	public static function renderPage($templateFileName, $data, $suffix = '.mustache')
	{
		self::header();
		self::render($templateFileName, $data, $suffix);
		self::footer();
	}
	
}