<?php
/**
 * Spotify Meta Data using the Spotify Metadata API
 *
 * @package putaspot
 * @author Niklas Lindblad
 */

class Spotify_com
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
		 * Check if provided content is a playlist or song.
		 * Spotify songs are handled using a helper method.
		 *
		 * @author Niklas Lindblad
		 */
		if ( ! preg_match('/playlist/', $content['url']) ) {
			return $this->song($content);
		}
		
		/**
		 * Currently Spotify does not have a public
		 * API for playlist meta data.
		 *
		 * @author Niklas Lindblad
		 */
		$content['service'] 	= 'spotify';
		$content['type']		= 'playlist';

		return $content;
	}
	
	/**
	 * Retrieve and set meta data for a Spotify song.
	 *
	 * @param array $content 	The user provided data
	 * @param array $newContent The new data
	 * @author Niklas Lindblad
	 */	
	public function song($content)
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