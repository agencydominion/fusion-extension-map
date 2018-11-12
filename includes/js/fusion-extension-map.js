/**
 * Scripts for Fusion Map Extension
 */

// Google Maps Function
function fsn_google_maps_init(lat,lng,mapID,places,zoomLevel,mapType,zoomControl,zoomControlPosition,typeControl,typeControlStyle,typeControlPosition, mapStyles,scaleControl){
	var infowindow = null;
	var latlng = new google.maps.LatLng(lat,lng);

	if(mapType == 'ROADMAP'){
		mapType = google.maps.MapTypeId.ROADMAP;
	}else if(mapType == 'SATELLITE'){
		mapType = google.maps.MapTypeId.SATELLITE;
	}else if(mapType == 'HYBRID'){
		mapType = google.maps.MapTypeId.HYBRID;
	}else if(mapType == 'TERRAIN'){
		mapType = google.maps.MapTypeId.TERRAIN;
	}

	if(typeControlStyle == 'DEFAULT'){
		typeControlStyle = google.maps.MapTypeControlStyle.DEFAULT;
	}else if(typeControlStyle == 'HORIZONTAL_BAR'){
		typeControlStyle = google.maps.MapTypeControlStyle.HORIZONTAL_BAR;
	}else if(typeControlStyle == 'DROPDOWN_MENU'){
		typeControlStyle = google.maps.MapTypeControlStyle.DROPDOWN_MENU;
	}

	mapStyles = JSON.parse(mapStyles);

	var options = {
	    center: latlng,
	    mapTypeId: mapType,
	    zoomControl: zoomControl,
	    zoomControlOptions: {
	        position: zoomControlPosition
	    },
	    mapTypeControl: typeControl,
	    mapTypeControlOptions: {
	        style: typeControlStyle,
	        position: typeControlPosition
	    },
	    fullscreenControl: false,
	    scaleControl: scaleControl,
	    streetViewControl: false,
	    zoom: zoomLevel,
			gestureHandling: 'cooperative',
	    styles: mapStyles,
	};
	var map = new google.maps.Map(document.getElementById(mapID), options);
	for (i = 0; i < places.length; i++) {
		if (places[i]['marker']['icon']) {
			var icon = fsnMapGetItemIconObject(places[i]['marker']['icon']);
			var marker = new google.maps.Marker({
					icon: icon,
					position: places[i]['marker']['position'],
					map: map,
					content: places[i]['infoWindow']['content']
			});
		} else {
			var marker = new google.maps.Marker({
					position: places[i]['marker']['position'],
					map: map,
					content: places[i]['infoWindow']['content']
			});
		}
		if (places[i]['infoWindow']['open'] == 'true') {
			infowindow = new google.maps.InfoWindow({
				content: places[i]['infoWindow']['content']
			});
			infowindow.open(map, marker);
			// var center = map.getCenter();
			// map.setCenter(center);
		}
		google.maps.event.addListener(marker, 'click', function() {
			if (infowindow) {
				infowindow.close();
			}
			infowindow = new google.maps.InfoWindow({
				content: this.content
			});
      infowindow.open(map, this);
    });
	}
	//recenter map on resize
	google.maps.event.addDomListener(window, "resize", function() {
		var center = map.getCenter();
		google.maps.event.trigger(map, "resize");
		map.setCenter(center);
	});
}

function fsnMapGetItemIconObject(icon) {
	var customMarkerUrl = icon.url;
	var customMarkerWidth = parseInt(icon.width);
	var customMarkerHeight = parseInt(icon.height);
	var customMarkerAnchorX = customMarkerWidth/2;
	switch(icon.anchorPosition) {
		case 'bottom_center':
			var customMarkerAnchorY = customMarkerHeight;
			break;
		case 'center':
			var customMarkerAnchorY = customMarkerHeight/2;
			break;
	}
	var customMarkerObject = {
		url : customMarkerUrl,
		scaledSize : {
			width : customMarkerWidth,
			height :  customMarkerHeight
		},
		anchor : new google.maps.Point(customMarkerAnchorX, customMarkerAnchorY)
	};
	return customMarkerObject;
}
