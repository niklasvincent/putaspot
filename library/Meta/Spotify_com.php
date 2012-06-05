<?php

class Spotify_com
{

	public function resolve($content)
	{
		$query = sprintf(
			'http://ws.spotify.com/lookup/1/.json?uri=%s', 
			$content['url']
		);
		
		$data = @json_decode(@file_get_contents($query), true);
				
		if ( ! $data ) {
			throw new Exception('Could not retrieve meta data.');
		}
		
		// Add additional meta-data
		$content['service'] 	= 'spotify';
		$content['type']		= 'song';
		$content['artist']		= $data['track']['artists'][0]['name'];
		$content['track']		= $data['track']['name'];
		$content['popularity']	= $data['track']['popularity'];
		$content['length']		= $data['track']['length'];
		
		return $content;
	}
	
}

?>