var app = Sammy('#main', function() {
			
	this.get('#/', function(context) {
	});
	
	this.get('#/:location', function(context) {
		$('#address').val(decodeURIComponent(context.params['location']));
		setTimeout(function(){ $('#geocode').submit(); }, 2000);
	});
	
	this.get('#/:zoom/:location', function(context) {
		$('#address').val(decodeURIComponent(context.params['location']));
		$('#zoom').val(decodeURIComponent(context.params['zoom']));
		setTimeout(function(){ $('#geocode').submit(); }, 2000);
	});
	
});

function single(spot, marker)
{
	$.ajax({
	  type: 'GET',
	  url: '/single.json?id='+spot._id.$id+'&lng='+realPosition.lng()+'&lat='+realPosition.lat(),
	  success: showMeta,
	  dataType: 'json'
	});	
}

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

function refreshMap()
{
	var bounds = map.getBounds();
	var ne = bounds.getNorthEast()
	var sw = bounds.getSouthWest();
	clearOverlays();
	getSpotsWithin(sw.lat(), sw.lng(), ne.lat(), ne.lng());
}

function clearOverlays() {
  if ( markersArray ) {
    for (var i = 0; i < markersArray.length; i++ ) {
      markersArray[i].setMap(null);
    }
  }
}

// Google Maps
var realPosition;
var map;
var done;
var createdMarker;
var markersArray = [];

function initialize(lat, lng) {
	done = false;
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
		
	google.maps.event.addListener(map, 'center_changed', refreshMap);
	google.maps.event.addListener(map, 'zoom_changed', refreshMap);
	placeMarker(latLng);
	getSpots(lat, lng);
}

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
	
	$('#lat').val(marker.getPosition().lat());
	$('#lng').val(marker.getPosition().lng());
	
	marker.setDraggable(true);
	
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
	
}

function resolveAddress()
{
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode( {'address': $('#address').val() }, function(data) {
		if ( typeof(data[0].geometry) != 'undefined' ) {
			var lat = data[0].geometry.location.$a;
			var lng = data[0].geometry.location.ab;
			var newPosition = new google.maps.LatLng(lat, lng);
			map.setCenter(newPosition);
			var zoom = parseInt($('#zoom').val());
			if ( zoom >= 11 ) {
				map.setZoom(zoom);
			}
			getSpots(lat, lng);
		}
	});
}

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

function gotLocation(position)
{
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
	initialize(lat, lng);
}

// start the application
$(document).ready(function() {
	
	var locationCheck = setInterval(function() { if ( map == null ) { getLocation(); } else { clearInterval(locationCheck); } }, 500);
	
	app.run('#/');
	
	$('#geocode').submit(function(e) {
    	e.preventDefault();
		resolveAddress();
	});
	
	$('#share_button').click(function(e) {
		var url = window.location.protocol + '//' + window.location.hostname + '/#/';
		var currentCenter = map.getCenter();
		url = url + map.getZoom() + '/' + currentCenter.lat() + '+' + currentCenter.lng();
		$('#share_link').val(url);
		$('#share_button').hide();
		$('#share_link').fadeIn('slow');
	});
	
	$('#submit').submit(function(e) {
    	e.preventDefault();
		var content = {
			"url": $('#url').val(),
			"loc": [$('#lat').val(), $('#lng').val()],
			"user_loc": [realPosition.lat(), realPosition.lng()]
		};
		
		createdMarker.setMap(null);
		
		$.ajax({
		  type: 'POST',
		  url: '/add',
		  data: JSON.stringify(content),
		  success: addSpot,
		  dataType: 'json'
		});
	});
	
});
