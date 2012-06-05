#Desktop Version
Google Maps API, HTML5 navigation (for positioning) and JQuery.

<img src="http://dl.dropbox.com/u/1236795/putaspot-desktop.png" />

#Mobile Version
jQuery Mobile, jQuery and HTML5 navigation (for positioning).

<img src="http://dl.dropbox.com/u/1236795/putaspot-mobile.png" />

##License

Distributed under the GNU AFFERO GENERAL PUBLIC LICENSE <http://www.gnu.org/licenses/agpl-3.0.html>

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
