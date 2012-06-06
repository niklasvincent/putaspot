<?php

class Mixcloud_com
{
	
	public function resolve($content)
	{
		// Mixcloud uses its own URL shortener
		if ( strstr($content['url'], 'i.mixcloud') ) {
			$headers = getHeadersForURL($content['url']);
			if ( isset($headers['Location']) ) {
				$content['url'] = $headers['Location'];
			} else {
				return $content;
			}
		}
		
		// "Objects in the Mixcloud API can be found by taking the URL 
		//  where you would find them on the site and changing 
		//  http://www.mixcloud.com/ to http://api.mixcloud.com/."
		$query = str_replace('http://www', 'http://api', $content['url']);
		
		$data = @json_decode(@file_get_contents($query), true);
				
		if ( ! $data ) {
			throw new Exception('Could not retrieve meta data.');
		}
		
		// Get rid of query strings that might mess up embed URL
		$parsed = parse_url(str_replace('http://www', 'http://api', $content['url']));
		$content['widget'] = sprintf('%s://%s%sembed-html/',
			$parsed['scheme'],
			$parsed['host'],
			$parsed['path']
		);
		
		$content['service'] 	= 'mixcloud';
		
		if ( preg_match('/\/track\//', $content['url']) ) {
			return $this->song($content, $data);
		}

		return $this->cast($content, $data);
	}
	
	private function song($content, $data)
	{
		$content['type']	= 'sound';
		$content['artist']	= $data['artist']['name'];
		$content['title']	= $data['name'];
		
		return $content;	
	}
	
	private function cast($content, $data)
	{
		$content['type']		= 'cast';
		$content['length']		= $data['audio_length'];
		$content['title']		= $data['name'];
		$content['description']	= $data['description'];
		$content['user']		= array( 
										'name' 		=> $data['user']['username'],
										'avatar'	=> $data['user']['pictures']['medium']
								);
		return $content;
	}
	
}

?>