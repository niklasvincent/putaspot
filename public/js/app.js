var app = Sammy('#main', function() {
			
	this.get('#/', function(context) {
		context.$element().html('');
		this.swap();
	});
	
});

function showMeta(spot, marker)
{
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
		$('#meta').html('<a href="'+spot.url+'" target="_blank">'+spot.url+'</a>').fadeIn();
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
  $('#input').fadeOut();
}

// Google Maps
var map;
var done;
var createdMarker;
var circle;

function initialize(lat, lng) {
	done = false;
	var latLng = new google.maps.LatLng(lat, lng);
	var myOptions = {
		zoom: 18,
		center: latLng,
		draggable: false,
		zoomable: false,
		disableDoubleClickZoom: true,
		panControl: false,
	    zoomControl: false,
	    scaleControl: false,
		keyboardShortcuts : false,
		navigationControl : false,
		scrollwheel : false,
		streetViewControl : false,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
  
	map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
	
	placeMarker(latLng);
	
	getSpots(lat, lng, function(spots) {
		spots = jQuery.parseJSON(spots);
		$.each(spots, function(index, spot) {
			addSpot(spot);
		});
	});
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
    console.log(event);
	  createdMarker.setPosition(event.latLng);
		var newPosition = createdMarker.getPosition();
		$('#lat').val(newPosition.lat());
		$('#lng').val(newPosition.lng());
    $('#input').offset({top: event.pixel.y, left: event.pixel.x+240}).fadeIn();
    $('#url').focus();
	});
	
	google.maps.event.addListener(marker, 'dragend', function() {
		var newPosition = marker.getPosition();
		$('#lat').val(newPosition.lat());
		$('#lng').val(newPosition.lng());
		checkInput(false);
	});
	
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
	
	$('#submit').submit(function(e) {
    e.preventDefault();
		var content = {
			"url": $('#url').val(),
			"loc": [$('#lat').val(), $('#lng').val()]
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
