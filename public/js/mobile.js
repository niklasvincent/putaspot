function getSpots(lat, lng, callback)
{
	$.ajax({
	  type: 'GET',
	  url: '/near.json?lat='+lat+'&lng='+lng,
	  success: callback,
	});
}

function gotLocation(position)
{
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
	initialize(lat, lng);
}

function initialize(lat, lng)
{
	getSpots(lat, lng, function(spots) {
		spots = jQuery.parseJSON(spots);
		$.each(spots, function(index, spot) {
			if ( spot.service === 'spotify' ) {
				addToList(spot.track, spot.artist, '/img/mobile/spotify.png', spot.url);
			} else if ( spot.service === 'youtube' ) {
				addToList(spot.title, '', '/img/mobile/youtube.png', spot.url);
			} else if ( spot.service === 'soundcloud' ) {
				addToList(spot.title, spot.user.name, '/img/mobile/soundcloud.png', spot.url);
			} else if ( spot.service === 'mixcloud' && spot.type == 'sound' ) {
				addToList(spot.title, spot.artist, '/img/mobile/mixcloud.png', spot.url);
			} else if ( spot.service === 'mixcloud' && spot.type == 'cast' ) {
				addToList(spot.title, spot.user.name, '/img/mobile/mixcloud.png', spot.url);
			}
		});
		$('#spots').listview('refresh');
	});
}

function addToList(header, subtitle, img, url)
{
	$('#spots').append('<li><a href="'+url+'"> \
		<img src="'+img+'" /> \
		<h3>'+header+'</h3> \
		<p>'+subtitle+'</p> \
	</a></li>');
}

// start the application
$(document).ready(function() {	
	
	if ( navigator.geolocation ) {
		navigator.geolocation.getCurrentPosition(gotLocation);
	} else {
		initialize(55.60934443876994, 13.002534539672865);
	}
	
});