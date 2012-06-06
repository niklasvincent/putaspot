<?php
/**
 * Various HTTP helper functions.
 *
 * @author Niklas Lindblad
 */

/**
 * Get HTTP headers as array for given URL
 *
 * @param string $url 			The URL
 * @return array $headers		The headers
 * @author Niklas Lindblad
 */
function getHeadersForURL($url)
{
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
	$header = curl_exec($ch);
	curl_close($ch);
	$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
	$results = array();
	foreach( $fields as $field ) {
		if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
			$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
			if( isset($results[$match[1]]) ) {
				$results[$match[1]] = array($results[$match[1]], $match[2]);
			} else {
				$results[$match[1]] = trim($match[2]);
			}
		}
	}
	return $results;
}

?>