#What is it?

Putaspot is meant to be a simple boilerplate for applications using geotagged content. The basic idea is that any URL can be associated with location data.

It was put together due to boredome during a couple of summer afternoons by <a href="http://twitter.com/nlindblad">@nlindblad</a> and <a href="http://twitter.com/mossisen">@Mossisen</a>.

#License

Distributed under the GNU AFFERO GENERAL PUBLIC LICENSE <http://www.gnu.org/licenses/agpl-3.0.html>.

All logos and trademarks are the property of the respective trademark owners.

#Screenshots

##Desktop Version
Google Maps API, HTML5 navigation (for positioning) and JQuery.

<img src="http://dl.dropbox.com/u/1236795/putaspot-desktop.png" />

##Mobile Version
jQuery Mobile, jQuery and HTML5 navigation (for positioning).

<img src="http://dl.dropbox.com/u/1236795/putaspot-mobile.png" />

#API

There are serveral ways to extend the functionality of this simple application. Either by integrating into other applications or extending the content handling directly.

##External (HTTP)
The same end-points used by both the desktop and mobile version can be used as a stand-alone API for other applications:

	GET /near.json?lat=53.967644&lng=13.993422
	
Will result in a JSON list of content near the given location:
	
	[
	    {
	        "_id": {
	            "$id": "4fce736227cd5c953a000000"
	        },
	        "url": "http://open.spotify.com/track/2iGeC71dO7dCR64972QPSX",
	        "loc": [
	            53.9676547,
	            13.9935683
	        ],
	        "added": 1338930018,
	        "expires": 1339534818,
	        "service": "spotify",
	        "type": "song",
	        "artist": "Bomfunk MC's",
	        "track": "Freestyler",
	        "popularity": "0.46590",
	        "length": 306
	    },
	    {
	        "_id": {
	            "$id": "4fce5d0427cd5c6830000000"
	        },
	        "url": "http://open.spotify.com/track/32kQfEUKkK69AvB6dhq2Fy",
	        "loc": [
	            53.967821261857,
	            13.993965139951
	        ],
	        "added": 1338924292,
	        "expires": 1339529092,
	        "service": "spotify",
	        "type": "song",
	        "artist": "Kraftwerk",
	        "track": "Computer Love",
	        "popularity": "0.00000",
	        "length": 435.573
	    }
	]

##Internal (Plugins)

Whenever a piece of content is analyzed by the back-end (before being added to the database) the top domain (e.g. "spotify.com") is extracted and formatted as

	$class = str_replace('.', '_', ucfirst($service));
	
which would turn "spotify.com" into "Spotify_com".

The next step is to try to find a matching file in the library/Meta/ directory. If a file is found, it is included a new instance of the presumed class is created:

	$meta = new $class;
	
In the last step the content array (so far only containing geotag and URL) is passed through the resolve() method of the plugin class:

	$content = $meta->resolve($content);
	
Inside the plugin class things like meta-data lookup can be done. Check out the default plugin classes that retrieves meta-data about content from Youtube, Spotify and Soundcloud.

##Install

Requirements:

* PHP5 (only tested with PHP 5.3)
* MongoDB extension for PHP
* MongoDB 2.0.5 (should work with as low as 1.9)

### Geospatial Indexing in MongoDB

To turn on Geospatial indexing:

	use YOURDATABASENAME;
	db.content.ensureIndex( { loc : "2d" } )

###Apache2 VirtualHost Example

	<VirtualHost *:80>
			ServerAdmin you@yourdomain.com
        	ServerName      yourdomain.com
			ServerAlias     youralias.com
			VirtualDocumentRoot /var/www/pathtoputaspot/public/
			<Directory /var/www/pathtoputaspot/public/>
				AllowOverride All
				Allow from All
				Satisfy Any
			</Directory>
			LimitInternalRecursion 15
			LogFormat "%v %h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-agent}i\"" vcommon
			ErrorLog /var/log/apache2/error.log
			LogLevel debug
 			CustomLog /var/log/apache2/access.log vcommon
	</VirtualHost>
	
###Application Configuration (config.ini)

	[database]
	name="YOURDATABASENAME"
	host="localhost"
	port=27017

	[putaspot]
	expiration=604800
	distance=0.0045026898
	
	[soundcloud]
	api_key="YOURSOUNDCLOUD_API_KEY"
	
* expiration: After this period (seconds) the content will no longer be listed.

* distance: Allowed distance to "discover" as a user. Defaults to about 250 meters.

* api_key: Soundcloud API key <http://developers.soundcloud.com/>
