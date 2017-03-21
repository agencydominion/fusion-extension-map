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
	
	var isDraggable = Modernizr.touchevents ? false : true;
	
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
	    scaleControl: scaleControl,
	    streetViewControl: false,
	    zoom: zoomLevel,
	    draggable: isDraggable,
	    scrollwheel: false,
	    styles: mapStyles
	};
	var map = new google.maps.Map(document.getElementById(mapID), options);
	for (i = 0; i < places.length; i++) {
		var marker = new google.maps.Marker({
		    icon: places[i]['marker']['icon'],
		    position: places[i]['marker']['position'],
		    map: map,
		    content: places[i]['infoWindow']['content']
		});
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