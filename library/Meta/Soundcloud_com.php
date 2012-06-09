<?php
/**
 * Soundcloud.com Meta Data using the Soundcloud.com API
 *
 * API key required to use their API.
 *
 * @package putaspot
 * @author Niklas Lindblad
 */

class Soundcloud_com
{
	
	/**
	 * Resolve content meta data.
	 *
	 * @param array $content 	Original user provided content data
	 * @return array $content	Content data with additionaly retrieved meta data
	 * @author Niklas Lindblad
	 */
	public function resolve($content)
	{
		global $config;
		
		$query = sprintf(
			'http://api.soundcloud.com/resolve.json?url=%s&client_id=%s',
			$content['url'],
			$config['soundcloud']['api_key']
		);
		
		$data = @json_decode(@file_get_contents($query), true);
				
		if ( ! $data ) {
			throw new Exception('Could not retrieve meta data.');
		}
				
		$content['service'] 	= 'soundcloud';
		$content['type']		= 'sound';
		$content['length']		= $data['duration'];
		$content['title']		= $data['title'];
		$content['description']	= $data['description'];
		$content['user']		= array( 
										'name' 		=> $data['user']['username'],
										'avatar'	=> $data['user']['avatar_url']
								);
		$content['tags']		= $data['tag_list'];
		$content['track_id']	= $data['id'];
		
		return $content;
	}
	
}

?>