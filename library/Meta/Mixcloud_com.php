<?php
/**
 * Mixcloud.com Meta Data using the Mixcloud.com API
 *
 * @package putaspot
 * @author Niklas Lindblad
 */

class Mixcloud_com
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
		/**
		 * Mixcloud uses its own URL shortener with the domain
		 * i.mixcloud.com. To get the URL necessary for API
		 * lookup, we have to check the 'Location' HTTP
		 * header that the short URL redirects to.
		 *
		 * @author Niklas Lindblad
		 */
		if ( strstr($content['url'], 'i.mixcloud') ) { 	
			$headers = getHeadersForURL($content['url']);
			if ( isset($headers['Location']) ) {
				$content['url'] = $headers['Location'];
			} else {
				return $content;
			}
		}
		
		/**
		 * From the Mixcloud API Documentation:
		 *
		 * "Objects in the Mixcloud API can be found by taking the URL 
		 *  where you would find them on the site and changing 
		 *  http://www.mixcloud.com/ to http://api.mixcloud.com/."
		 *
		 * @author Niklas Lindblad
		 */
		$query = str_replace('http://www', 'http://api', $content['url']);
		
		$data = @json_decode(@file_get_contents($query), true);
				
		if ( ! $data ) {
			throw new Exception('Could not retrieve meta data.');
		}
		
		/**
		 * Mixcloud.com likes to keep a lot of query strings in their
		 * URLs for tracking things like campaigns and referers.
		 *
		 * These query parameters must be removed in order to get the
		 * correct URL for the API call.
		 *
		 * @author Niklas Lindblad
		 */
		$parsed = parse_url(str_replace('http://www', 'http://api', $content['url']));
		$content['widget'] = sprintf('%s://%s%sembed-html/',
			$parsed['scheme'],
			$parsed['host'],
			$parsed['path']
		);
		
		$content['service'] 	= 'mixcloud';
		
		/**
		 * Determine if the Mixcloud content is a track.
		 * If so, some additional meta data is added
		 * by a helper method.
		 *
		 * @author Niklas Lindblad
		 */
		if ( preg_match('/\/track\//', $content['url']) ) {
			return $this->song($content, $data);
		}

		/**
		 * The default case is a Mixcloud cast.
		 * Meta data is added by a helper
		 * method.
		 * 
		 * @author Niklas Lindblad
		 */
		return $this->cast($content, $data);
	}
	
	/**
	 * Determine which meta data to keep for a Mixcloud song.
	 *
	 * @param array $content 	The user provided data
	 * @param array $data 		The API provided data
	 * @param array $newContent The new data combination
	 * @author Niklas Lindblad
	 */
	private function song($content, $data)
	{
		$content['type']	= 'sound';
		$content['artist']	= $data['artist']['name'];
		$content['title']	= $data['name'];
		
		return $content;	
	}

	/**
	 * Determine which meta data to keep for a Mixcloud cast.
	 *
	 * @param array $content 	The user provided data
	 * @param array $data 		The API provided data
	 * @param array $newContent The new data combination
	 * @author Niklas Lindblad
	 */	
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