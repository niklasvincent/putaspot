<?php
/**
 * Youtube Meta Data using the Youtube API
 *
 * @package putaspot
 * @author Niklas Lindblad
 */

class Youtube_com
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
		 * Video ID is given by the 'v' URL parameter.
		 *
		 * @author Niklas Lindblad
		 */
		parse_str(parse_url($content['url'], PHP_URL_QUERY), $output);
		$query = sprintf(
			'http://gdata.youtube.com/feeds/api/videos?q=%s&max-results=1&v=2&alt=jsonc',
			$output['v']
		);
		
		$data = @json_decode(@file_get_contents($query), true);
				
		if ( ! $data ) {
			throw new Exception('Could not retrieve meta data.');
		}
		
		/**
		 * Since we search for a unique ID, the first
		 * result is always the one we want.
		 *
		 * @author Niklas Lindblad
		 */
		$data = $data['data']['items'][0];
				
		$content['service'] 		= 'youtube';
		$content['type']			= 'video';
		$content['title']			= $data['title'];
		$content['length']			= $data['duration'];
		$content['video_id']		= $data['id'];
		$content['description']		= $data['description'];
		$content['thumbnail']		= $data['thumbnail']['hqDefault'];
		
		return $content;
	}
	
}

?>