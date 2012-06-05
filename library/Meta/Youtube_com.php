<?php

class Youtube_com
{
	
	public function resolve($content)
	{
		parse_str(parse_url($content['url'], PHP_URL_QUERY), $output);
		$query = sprintf(
			'http://gdata.youtube.com/feeds/api/videos?q=%s&max-results=1&v=2&alt=jsonc',
			$output['v']
		);
		
		$data = @json_decode(@file_get_contents($query), true);
				
		if ( ! $data ) {
			throw new Exception('Could not retrieve meta data.');
		}
		
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