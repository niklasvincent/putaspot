/**
 * Variables used by the application.
 *
 * @author Niklas Lindblad
 */
var realPosition;		// The user's real position 
var map;				// The Google Map instance
var done;				// Whether the input marker has been placed
var createdMarker;		// Newly created marker object
var markersArray = [];	// Array of all markers on the map

/**
 * Define our routes using Sammy.
 */
var app = Sammy('#main', function() {
			
	/**
	 * The default GET / does not have any assigned functionality
	 * except rending the page.
	 */
	this.get('#/', function(context) {
	});
	
	/**
	 * If a location was passed in the URL, it will be geocoded
	 * and the map will automatically change location.
	 */
	this.get('#/:location', function(context) {
		/*
		 * Basically: Set the input 'location' field to the value
		 * from the URL and then trigger the submit() on the form.
		 */
		$('#address').val(decodeURIComponent(context.params['location']));
		setTimeout(function(){ $('#geocode').submit(); }, 2000);
	});
	
	/**
	 * Since the map will not look identical without the proper zoom
	 * level, there is an option to include a zoom level along with
	 * the location string.
	 */
	this.get('#/:zoom/:location', function(context) {
		$('#address').val(decodeURIComponent(context.params['location']));
		$('#zoom').val(decodeURIComponent(context.params['zoom']));
		setTimeout(function(){ $('#geocode').submit(); }, 2000);
	});
	
});

/**
 * Retrive information about a single spot.
 *
 * @param spot		The spot JSON data
 * @param marker 	The marker representing the spot on the map
 * @callback		Calls showMeta() on success
 * @author Niklas Lindblad
 */
function single(spot, marker)
{
	$.ajax({
	  type: 'GET',
	  url: '/single.json?id='+spot._id.$id+'&lng='+realPosition.lng()+'&lat='+realPosition.lat(),
	  success: showMeta,
	  dataType: 'json'
	});	
}

/**
 * Show meta data about a given spot.
 *
 * Triggered by the single() method on success.
 *
 * @param spot		The spot JSON data
 * @param marker 	The marker representing the spot on the map
 * @author Niklas Lindblad
 */
function showMeta(spot, marker)
{
  if ( spot.error == 'TOO_FAR_AWAY' ) {
    alert('Ooop! That spot\'s a bit far away. Guess you\'ll have to go there!');
    return;
  }
	if ( null === spot ) {
		return;
	}
	if ( typeof(spot.url) === 'undefined' ) {
		return;
	}
	$('#info').fadeOut();
	if ( spot.service === 'spotify' && spot.type === 'song' ) {
		$('#meta').html('<iframe src="https://embed.spotify.com/?uri='+spot.url+'" width="300" height="380" frameborder="0" allowtransparency="true"></iframe>').fadeIn();
	} else if ( spot.service === 'spotify' && spot.type === 'playlist' ) {
		$('#meta').html('<iframe src="https://embed.spotify.com/?uri='+spot.url+'&theme=white" width="300" height="380" frameborder="0" allowtransparency="true"></iframe>').fadeIn();
	} else if ( spot.service === 'soundcloud' ) {
		$('#meta').html('<iframe width="100%" height="166" scrolling="no" frameborder="no" src="http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F'+spot.track_id+'&show_artwork=true"></iframe>').fadeIn();
	} else if ( spot.service === 'youtube' ) {
		$('#meta').html('<iframe width="560" height="315" src="http://www.youtube.com/embed/'+spot.video_id+'" frameborder="0" allowfullscreen></iframe>').fadeIn();
	} else if ( spot.service === 'mixcloud' && spot.type === 'cast' ) {
		$('#meta').html('<iframe width="560" height="315" src="'+spot.widget+'" frameborder="0" allowfullscreen></iframe>').fadeIn();
	} else {
		window.location = spot.url;
	}
}

/**
 * Get all spots near a given latitude / longitude.
 *
 * Calls addSpot() for each spot retrieved.
 *
 * @param lat	Latitude
 * @param lng 	Longitude
 * @author Niklas Lindblad
 */
function getSpots(lat, lng)
{
	$.ajax({
	  type: 'GET',
	  url: '/near.json?lat='+lat+'&lng='+lng,
	  success: function(spots) {
			spots = jQuery.parseJSON(spots);
			$.each(spots, function(index, spot) {
				addSpot(spot);
			});
	  }
	});
}

/**
 * Get all spots within a box described by the coordinates
 * of two corners.
 *
 * Calls addSpot() for each spot retrieved.
 *
 * @param lat1	Latitude for the first corner
 * @param lng1	Longitude for the first corner
 * @param lat2	Latitude for the second corner
 * @param lng2	Longitude for the second corner
 * @author Niklas Lindblad
 */
function getSpotsWithin(lat1, lng1, lat2, lng2)
{
	$.ajax({
	  type: 'GET',
	  url: '/within.json?lat1='+lat1+'&lng1='+lng1+'&lat2='+lat2+'&lng2='+lng2,
	  success: function(spots) {
			spots = jQuery.parseJSON(spots);
			$.each(spots, function(index, spot) {
				addSpot(spot);
			});
	  }
	});
}

/**
 * Update map to include marker for newly created spot.
 *
 * Calls addSpot() for each spot retrieved.
 *
 * @param spot	The newly created spot
 * @author Niklas Lindblad
 */
function addSpot(spot)
{
  if (spot.error == "TOO_FAR_AWAY") {
    alert("Hey you! You're not even near that spot!");
    $('#input').fadeOut();
    return false;
  }
	var position = new google.maps.LatLng(spot.loc[0], spot.loc[1]);
	var marker = new google.maps.Marker({
		position: position,
		map: map
	});
	markersArray.push(marker);
	if ( typeof(spot.service) === 'undefined' ) {
		marker.setIcon('/img/markers/default.png');
	} else {
		marker.setIcon('/img/markers/'+spot.service+'.png');
	}
	google.maps.event.addListener(marker, 'click', function() {
    	single(spot, marker);
  	});
  $('#input').fadeOut();
}

/**
 * Refresh the map (clear previous markers and retrieve new ones).
 *
 * Uses getSpotsWithin() with parameters from the Google Map object.
 *
 * @author Niklas Lindblad
 */
function refreshMap()
{
	var bounds = map.getBounds();
	var ne = bounds.getNorthEast()
	var sw = bounds.getSouthWest();
	clearOverlays();
	getSpotsWithin(sw.lat(), sw.lng(), ne.lat(), ne.lng());
}

/**
 * Remove all existing markers form the Google Map.
 *
 * @author Niklas Lindblad
 */
function clearOverlays() {
  if ( markersArray ) {
    for (var i = 0; i < markersArray.length; i++ ) {
      markersArray[i].setMap(null);
    }
  }
}

/**
 * Initialize the Google Map instance.
 *
 * Also places a marker at the user's current position.
 *
 * Triggers getSpots() to fill the map.
 *
 * @param lat	Latitude
 * @param lng	Longitude
 * @author Niklas Lindblad
 */
function initialize(lat, lng) {
	done = false;
	
	/**
	 *	Convert given latitude / longitude to LatLng object.
	 */
	var latLng = new google.maps.LatLng(lat, lng);
	realPosition = latLng;
	
	var myOptions = {
		zoom: 18,
		center: latLng,
		draggable: true,
		zoomable: true,
		disableDoubleClickZoom: true,
		panControl: true,
	    zoomControl: true,
	    scaleControl: true,
		keyboardShortcuts : true,
		navigationControl : true,
		scrollwheel : true,
		streetViewControl : false,
		minZoom: 11,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
  
	map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
	
	/**
	 * Make sure the map is refreshed whenever the user zooms or pans.
	 */	
	google.maps.event.addListener(map, 'center_changed', refreshMap);
	google.maps.event.addListener(map, 'zoom_changed', refreshMap);
		
	placeMarker(latLng);
	getSpots(lat, lng);
}

/**
 * Place an input marker at the given location.
 *
 * Will show the URL input field on click and after
 * being dragged.
 *
 * @param location LatLng object
 * @author Niklas Lindblad
 */
function placeMarker(location) {
	if ( done == false ) {
		var marker = new google.maps.Marker({
			position: location,
			map: map
		});
		createdMarker = marker;
		done = true;
	} else {
		return;
	}
	
	/**
	 * Update the form fields (hidden) that holds the coordinates.
	 */
	$('#lat').val(marker.getPosition().lat());
	$('#lng').val(marker.getPosition().lng());
	
	marker.setDraggable(true); // Make it draggable
	
	google.maps.event.addListener(map, 'click', function(event) {
	  createdMarker.setPosition(event.latLng);
		var newPosition = createdMarker.getPosition();
		$('#lat').val(newPosition.lat());
		$('#lng').val(newPosition.lng());
    	$('#input').offset({top: event.pixel.y, left: event.pixel.x+240}).fadeIn();
    	$('#url').focus();
	});
	
	google.maps.event.addListener(marker, 'dragend', function(event) {
		var newPosition = marker.getPosition();
		$('#lat').val(newPosition.lat());
		$('#lng').val(newPosition.lng());
		$('#input').offset({top: event.pixel.y, left: event.pixel.x+240}).fadeIn();
	    $('#url').focus();
	});
	
	/**
	 *  TODO: Make a new function for the input field placement to reduce
	 *        code duplicity.
	 */
	
}

/**
 * Resolve the address in the location text field.
 *
 * Uses the Google Maps provided Geocoder.
 *
 * After the address is resolved, update map and
 * retrieve nearby spots.
 *
 * @author Niklas Lindblad
 */
function resolveAddress()
{
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode( {'address': $('#address').val() }, function(results, status) {
		if ( status == google.maps.GeocoderStatus.OK && results[0]) {
			var newLat = results[0].geometry.location.lat();
			var newLng = results[0].geometry.location.lng();
			var newPosition = new google.maps.LatLng(newLat, newLng);
			map.setCenter(newPosition);
			var zoom = parseInt($('#zoom').val());
			if ( zoom >= 11 ) {
				map.setZoom(zoom);
			}
			getSpots(newLat, newLng);
		}
	});
}

/**
 * Get user's current location using the new Geolocation
 * API Specification from W3C:
 *
 * <http://dev.w3.org/geo/api/spec-source.html>
 *
 * Will show the URL input field on click and after
 * being dragged.
 *
 * @author Niklas Lindblad
 */
function getLocation()
{
	if ( map != null ) {
		return;
	}
	if ( navigator.geolocation ) {
  		navigator.geolocation.getCurrentPosition(gotLocation, function() { return; }, {enableHighAccuracy:true, maximumAge:30000, timeout:27000});
	} else {
		initialize(55.60934443876994, 13.002534539672865);
	}
}

/**
 * Called after a successfull location lookup in getLocation().
 *
 * Initializes the Google Map by calling initialize().
 *
 * Will show the URL input field on click and after
 * being dragged.
 *
 * @author Niklas Lindblad
 */
function gotLocation(position)
{
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
	initialize(lat, lng);
}

/**
 * Some things that need to happen as soon as the DOM is ready.
 *
 * @author Niklas Lindblad
 */
$(document).ready(function() {
	
	/**
	 * Since some devices (especially Android, it seems) takes
	 * a long time retrieving the current position, the location
	 * is checked every 500 ms to compensate for the difference
	 * in initialization time.
	 *
	 * Since I do not own an Android device, I cannot verify
	 * that this works all the time.
	 *
	 * @author Niklas Lindblad
	 */
	var locationCheck = setInterval(function() { if ( map == null ) { getLocation(); } else { clearInterval(locationCheck); } }, 500);
	
	/**
	 * Run the application using Sammy.
	 *
	 * @author Niklas Lindblad
	 */
	app.run('#/');
	
	/**
	 * Define submit() event for the Geocode address input field.
	 *
	 * @author Niklas Lindblad
	 */
	$('#geocode').submit(function(e) {
    	e.preventDefault();
		resolveAddress();
	});
	
	/**
	 * Define click() event for the location sharing button.
	 *
	 * Basically constructs the URL for the current map view
	 * and puts in a text field where it can be copied and
	 * shared.
	 *
     * @author Niklas Lindblad
	 */
	$('#share_button').click(function(e) {
		var url = window.location.protocol + '//' + window.location.hostname + '/#/';
		var currentCenter = map.getCenter();
		url = url + map.getZoom() + '/' + currentCenter.lat() + '+' + currentCenter.lng();
		$('#share_link').val(url);
		$('#share_button').hide();
		$('#share_link').fadeIn('slow');
	});
	
	/**
	 * Define submit() event for the URL input field.
	 *
	 * @author Niklas Lindblad
	 */
	$('#submit').submit(function(e) {
    	e.preventDefault();
		var content = {
			"url": $('#url').val(),
			"loc": [$('#lat').val(), $('#lng').val()],
			"user_loc": [realPosition.lat(), realPosition.lng()]
		};
		
		createdMarker.setMap(null); // Remove the input marker
		
		/**
		 * AJAX call to add the new spot.
		 *
		 * @callback addSpot() on success.
		 * @author Niklas Lindblad
		 */
		$.ajax({
		  type: 'POST',
		  url: '/add',
		  data: JSON.stringify(content),
		  success: addSpot,
		  dataType: 'json'
		});
	});
	
});
