<?php

class Soundcloud_com
{
	
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