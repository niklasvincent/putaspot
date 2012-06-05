var app = Sammy('#main', function() {
			
	this.get('#/', function(context) {
		context.$element().html('');
		this.swap();
	});
	
});

function showMeta(spot, marker)
{
	if ( spot.service === 'spotify' && spot.type === 'song' ) {
		$('#meta').html('<iframe src="https://embed.spotify.com/?uri='+spot.url+'" width="300" height="380" frameborder="0" allowtransparency="true"></iframe>');
	} else if ( spot.service === 'spotify' && spot.type === 'playlist' ) {
		$('#meta').html('<iframe src="https://embed.spotify.com/?uri='+spot.url+'&theme=white" width="300" height="380" frameborder="0" allowtransparency="true"></iframe>');
	} else if ( spot.service === 'soundcloud' ) {
		$('#meta').html('<iframe width="100%" height="166" scrolling="no" frameborder="no" src="http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F'+spot.track_id+'&show_artwork=true"></iframe>');
	} else if ( spot.service === 'youtube' ) {
		$('#meta').html('<iframe width="560" height="315" src="http://www.youtube.com/embed/'+spot.video_id+'" frameborder="0" allowfullscreen></iframe>')
	} else {
		$('#meta').html('<a href="'+spot.url+'" target="_blank">'+spot.url+'</a>');
	}
}

function getSpots(lat, lng, callback)
{
	$.ajax({
	  type: 'GET',
	  url: '/near.json?lat='+lat+'&lng='+lng,
	  success: callback,
	});
}

function addSpot(spot)
{
	var position = new google.maps.LatLng(spot.loc[0], spot.loc[1]);
	var marker = new google.maps.Marker({
		position: position,
		map: map
	});
	if ( typeof(spot.service) === 'undefined' ) {
		marker.setIcon('/img/markers/default.png');
	} else {
		marker.setIcon('/img/markers/'+spot.service+'.png');
	}
	google.maps.event.addListener(marker, 'click', function() {
    	showMeta(spot, marker);
  	});
}

// Google Maps
var map;
var done;
var createdMarker;

function initialize(lat, lng) {
	done = false;
	var latLng = new google.maps.LatLng(lat, lng);
	var myOptions = {
		zoom: 13,
		center: latLng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
  
	map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
	
	google.maps.event.addListener(map, 'click', function(event) {
	    placeMarker(event.latLng);
	});
	
	getSpots(lat, lng, function(spots) {
		spots = jQuery.parseJSON(spots);
		$.each(spots, function(index, spot) {
			addSpot(spot);
		});
	});
}

function checkInput(override)
{
	if ( ( $('#url').val().length > 0 || override ) && done ) {
		$('#create_button').fadeIn('slow');
	}	
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
	
	checkInput(false);
	
	marker.setDraggable(true);
	
	google.maps.event.addListener(marker, 'dragend', function() {
		var newPosition = marker.getPosition();
		$('#lat').val(newPosition.lat());
		$('#lng').val(newPosition.lng());
		checkInput(false);
	});
	
	map.setCenter(location);
}

function gotLocation(position)
{
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
	initialize(lat, lng);
}

// start the application
$(document).ready(function() {	
	app.run('#/');
	
	if ( navigator.geolocation ) {
	  navigator.geolocation.getCurrentPosition(gotLocation);
	} else {
		initialize(55.60934443876994, 13.002534539672865);
	}
	
	$('#url').change(function() {
		checkInput(false);
	});
	
	$('#url').keypress(function() {
		checkInput(false);
	});
	
	$("#url").bind('paste', function() {
		checkInput(true);	
	});
	
	$('#create_button').click(function() {
		var content = {
			"url": $('#url').val(),
			"loc": [$('#lat').val(), $('#lng').val()]
		};
		$('#create_button').hide();
		$('#url').hide();
		
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