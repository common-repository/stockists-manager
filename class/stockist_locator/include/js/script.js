var stockist_map;
var stockist_markers = [];
var stockist_infoWindow;
var stockist_panorama;

jQuery('#geocode_address_btn').live('click', function(event) {
	event.preventDefault();
	var address = jQuery('#address2geocode').val();
	
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({address: address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			var lat = results[0].geometry.location.lat();
			var lng = results[0].geometry.location.lng();
			jQuery('#add_stockist_form').hide();
			jQuery('#add_stockist_form2').show();
			jQuery('#lat').val(lat);
			jQuery('#lng').val(lng);
			jQuery('#address').val(address);
			jQuery('#address_display').html(address);
			var img = '<img src="http://maps.google.com/maps/api/staticmap?center='+lat+','+lng+'&zoom=15&size=300x200&markers=color:red|'+lat+','+lng+'&sensor=false">';
			jQuery('#map_display').html(img);
		}
	});
});

jQuery('#edit_geocode_address').live('click', function(event) {
	event.preventDefault();
	jQuery('#add_stockist_form').show();
	jQuery('#add_stockist_form2').hide();
	jQuery('#address2geocode').val(jQuery('#address').val());
});

// ###################
// START Widget stockists
function display_widget_closest_stockists() {
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(closest_stockists_detectionSuccess, closest_stockists_detectionError, {maximumAge:Infinity});
	}
}

function closest_stockists_detectionSuccess(position) {
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
	
	var page_number = 1;
	
	//loading icon
	if(jQuery('#widget_stockist_locator_list').html()=='') {
		var img = '<img src="' + Stockist.plugin_url + 'graph/ajax-loader.gif">';
		jQuery('#widget_stockist_locator_list').html(img);
	}
	
	jQuery.ajax({
		type: 'POST',
		url: Stockist.ajaxurl,
		dataType: 'json',
		data: 'action=stockist_listener&method=display_list&page_number=' + page_number + '&lat=' + lat + '&lng=' + lng + '&nb_display=' + Stockist.widget_nb_display + '&no_info_links=1&widget_display=1&category_id='+Stockist.category_id+'&product='+Stockist.product_slug,
		success: function(msg) {
			var stockists = msg.stockists;
			jQuery('#widget_stockist_list').html(stockists);
		}
	});
}

function closest_stockists_detectionError() {
	jQuery('#widget_stockist_list').html('You need to share your location in order to view the locations list. <a href="javascript:window.location.reload();">Reload the page?</a>');
}
// ##################
// END Widget stockists

// ####################
// START closest stockists
function search_closest_locations() {
	if (navigator.geolocation) {
  		navigator.geolocation.getCurrentPosition(search_closest_locations_success, search_closest_locations_error, {maximumAge:Infinity});
	}
}

function search_closest_locations_success(position) {
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
	Stockist.current_lat = lat;
	Stockist.current_lng = lng;
	search_locations2();
}

function search_closest_locations_error() {
	search_locations2();
}
// ##################
// END closest stockists

function init_basic_map(lat, lng, marker_text, marker_icon) {
	stockist_map = new google.maps.Map(document.getElementById("map"), {
		center: new google.maps.LatLng(lat, lng),
		zoom: Stockist.zoom_detail,
		scrollwheel: false,
		mapTypeId: Stockist.map_type,
		mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DEFAULT}
	});
	
	var latlng = new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
	
	createMarker(latlng, lat, lng, marker_text, marker_icon);
	
	if(Stockist.streetview=='on') streetView(lat,lng);
	
	stockist_infoWindow = new google.maps.InfoWindow();
}

function stockist_load(type) {
	if(type=='map') {
		jQuery('body').data('type', 'map');
		init_stockists_map();
	}
	else if(type=='both') {
		jQuery('body').data('type', 'both');
		init_stockists_map_list();
	}
	else {
		jQuery('body').data('type', 'list');
		init_stockists_list();
	}
}

function init_stockists_map_list() {
	init_stockists_map();
	init_stockists_list();
}

function init_stockists_list() {
	//loading icon
	if(jQuery('#stockist_list').html()=='') {
		var img = '<img src="' + Stockist.plugin_url + 'graph/ajax-loader.gif">';
		jQuery('#stockist_list').html(img);
	}
	
	//page number setup
	var page_number = jQuery('body').data("page_number");
	if(page_number==null) {
		page_number=1;
		jQuery('body').data("page_number", page_number);
	}
	
	//lat & lng setup
	var lat = '';
	var lng = '';
	if(Stockist.searched_lat!='' && Stockist.searched_lng!='') {
		lat = Stockist.searched_lat;
		lng = Stockist.searched_lng;
	}
	
	jQuery.ajax({
		type: 'POST',
		url: Stockist.ajaxurl,
		dataType: 'json',
		data: 'action=stockist_listener&method=display_list&page_number=' + page_number + '&lat=' + lat + '&lng=' + lng + '&category_id=' + Stockist.category_id + '&category2_id=' + Stockist.category2_id + '&radius_id=' + Stockist.radius_id + '&nb_display=' + Stockist.nb_display + '&display_type=' + jQuery('body').data('type')+'&product='+Stockist.product_slug,
		success: function(msg) {
			jQuery('#stockist_title').html(msg.title);
			
			if(msg.stockists=='') jQuery('#stockist_list').html('No results found');
			else jQuery('#stockist_list').html(msg.stockists);
			
			jQuery('#previousNextButtons').html(msg.previousNextButtons);
			if (jQuery('#previousNextButtons2').length > 0) jQuery('#previousNextButtons2').html(msg.previousNextButtons);
		}
	});
}

function init_stockists_map() {
	
	if(Stockist.current_lat=='') {
		stockist_map = new google.maps.Map(document.getElementById("map"), {
			center: new google.maps.LatLng(Stockist.lat, Stockist.lng),
			zoom: Stockist.zoom,
			scrollwheel: false,
			mapTypeId: Stockist.map_type,
			mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DEFAULT}
		});
		stockist_infoWindow = new google.maps.InfoWindow();
	}
	
	if(Stockist.search=='on' || jQuery('#Stockist_address').val()!='') {
		search_locations();
	}
}

function search_locations() {
	if(Stockist.closest_stockists=='on') {
		if(Stockist.current_lat!='' && Stockist.current_lng!='') {
			search_locations2();
		}
		else {
			search_closest_locations();
		}
	}
	else {
		search_locations2();
	}
}

jQuery(".displayStockistMap").live('click', function(event) {
	event.preventDefault();
	var id = jQuery(this).attr('id');
	var lat = jQuery(this).attr('lat');
	var lng = jQuery(this).attr('lng');
	
	var content = jQuery('#infowindow_'+id).html();
	var marker_icon = jQuery('#marker_icon_'+id).html();
	
	var latlng = new google.maps.LatLng(
		parseFloat(lat),
		parseFloat(lng)
	);
	
	init_basic_map(lat, lng, '', '');
	
	clearLocations();
	createMarker(latlng, lat, lng, content, marker_icon, 1);
});

function search_locations2() {
	
	//page number init
	var page_number = jQuery('body').data("page_number");
	if(page_number==null) {
		page_number=1;
		jQuery('body').data("page_number", page_number);
	}
	
	//lat & lng setup
	var lat = '';
	var lng = '';
	if(Stockist.searched_lat!='' && Stockist.searched_lng!='') {
		lat = Stockist.searched_lat;
		lng = Stockist.searched_lng;
	}
	if( Stockist.current_lat!='' && Stockist.current_lng!='' ){
		lat = Stockist.current_lat;
		lng = Stockist.current_lng;
	}
	
	var nb_display;
	nb_display = Stockist.nb_display;
	
	jQuery.ajax({
		type: 'POST',
		url: Stockist.ajaxurl,
		dataType: 'json',
		data: 'action=stockist_listener&method=display_map&page_number=' + page_number + '&lat=' + lat + '&lng=' + lng + '&category_id=' + Stockist.category_id + '&category2_id=' + Stockist.category2_id + '&radius_id=' + Stockist.radius_id + '&nb_display=' + nb_display+'&product='+Stockist.product_slug,
		success: function(msg) {
			var locations = msg.locations;
            if( locations.length > 0 ) {
                var markersContent = msg.markersContent;
                var bounds = new google.maps.LatLngBounds();

                jQuery('#stockist_title').html(msg.title);
                jQuery('#previousNextButtons').html(msg.previousNextButtons);
                if (jQuery('#previousNextButtons2').length > 0) jQuery('#previousNextButtons2').html(msg.previousNextButtons);
                clearLocations();

                for (var i = 0; i < locations.length; i++) {
                    var name = locations[i]['name'];
                    var address = locations[i]['address'];
                    var distance = parseFloat(locations[i]['distance']);
                    var latlng = new google.maps.LatLng(
                        parseFloat(locations[i]['lat']),
                        parseFloat(locations[i]['lng'])
                    );
                    //category custom marker
                    var marker_icon = locations[i]['marker_icon'];

                    //if no category marker, set custom marker
                    //if(marker_icon==null) marker_icon = Stockist.custom_marker;

                    //createOption(name, distance, i);
                    createMarker(latlng, locations[i]['lat'], locations[i]['lng'], markersContent[i], marker_icon);

                    bounds.extend(latlng);
                }

                if (locations.length > 1) {
                    stockist_map.fitBounds(bounds);
                }
                else {
                    stockist_map.setCenter(bounds.getCenter());
                    stockist_map.setZoom(15);
                }
            }
		}
	});
}

function clearLocations() {
	if( typeof stockist_infoWindow != 'undefined' )
		stockist_infoWindow.close();
	for (var i = 0; i < stockist_markers.length; i++) {
		stockist_markers[i].setMap(null);
	}
	stockist_markers.length = 0;
}

function Stockist_setAddress(address, display_type) {
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({address: address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			Stockist.current_lat = results[0].geometry.location.lat();
			Stockist.current_lng = results[0].geometry.location.lng();
			
			Stockist.searched_lat = results[0].geometry.location.lat();
			Stockist.searched_lng = results[0].geometry.location.lng();
			
			stockist_load(display_type);
		}
	});
}

jQuery("#Stockist_category_filter").live('change', function(event) {
	event.preventDefault();
	var category_id = jQuery(this).val();
	jQuery('body').data('page_number', 1);
	Stockist.category_id = category_id;
	
	if(jQuery('body').data('type')=='map') search_locations();
	else if(jQuery('body').data('type')=='both') init_stockists_map_list();
	else init_stockists_list();
});

jQuery("#Stockist_category2_filter").live('change', function(event) {
	event.preventDefault();
	var category2_id = jQuery(this).val();
	jQuery('body').data('page_number', 1);
	Stockist.category2_id = category2_id;
	
	if(jQuery('body').data('type')=='map') search_locations();
	else if(jQuery('body').data('type')=='both') init_stockists_map_list();
	else init_stockists_list();
});

jQuery("#Stockist_distance_filter").live('change', function(event) {
	event.preventDefault();
	var radius_id = jQuery(this).val();
	jQuery('body').data('page_number', 1);
    Stockist.radius_id = radius_id;
	
	if(jQuery('body').data('type')=='map') search_locations();
	else if(jQuery('body').data('type')=='both') init_stockists_map_list();
	else init_stockists_list();
});

jQuery("#Stockist_search_btn").live('click', function(event) {
	event.preventDefault();
	var address = jQuery("#Stockist_address").val();
	jQuery('body').data("page_number", 1);
	
	//suffix
	//address += ', Australia';
	
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({address: address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			
			Stockist.searched_lat = results[0].geometry.location.lat();
			Stockist.searched_lng = results[0].geometry.location.lng();
			
			if(jQuery('body').data('type')=='map') search_locations();
			else if(jQuery('body').data('type')=='both') init_stockists_map_list();
			else init_stockists_list();
		}
		else {
			Stockist.searched_lat = '';
			Stockist.searched_lng = '';
			if(jQuery('body').data('type')=='map') search_locations();
			else if(jQuery('body').data('type')=='both') init_stockists_map_list();
			else init_stockists_list();
		}
	});
});

function setStreetView(latlng) {
    stockist_panorama = stockist_map.getStreetView();
    stockist_panorama.setPosition(latlng);
    stockist_panorama.setPov({
      heading: 265,
      zoom:1,
      pitch:0}
    );
}

jQuery("#displayStreetView").live('click', function(event) {
	event.preventDefault();
	//alert('cool');
	stockist_panorama.setVisible(true);
});

jQuery("#stockist_next").live('click', function(event) {
	event.preventDefault();
	var page_number = jQuery('body').data("page_number");
	jQuery('body').data("page_number", (page_number+1));
	if(jQuery('body').data('type')=='map') search_locations();
	else if(jQuery('body').data('type')=='both') init_stockists_map_list();
	else init_stockists_list();
});

jQuery("#stockist_previous").live('click', function(event) {
	event.preventDefault();
	var page_number = jQuery('body').data("page_number");
	jQuery('body').data("page_number", (page_number-1));
	if(jQuery('body').data('type')=='map') search_locations();
	else if(jQuery('body').data('type')=='both') init_stockists_map_list();
	else init_stockists_list();
});

function createMarker(latlng, lat, lng, html, marker_icon, window_flag) {
	
	if(marker_icon===null || marker_icon===undefined || marker_icon==='') marker_icon=Stockist.custom_marker;
	
	var marker = new google.maps.Marker({
		map: stockist_map,
		position: latlng,
		icon: marker_icon,
		animation: google.maps.Animation.DROP
	});
	
	if(window_flag==1) {
		stockist_infoWindow.setContent(html);
		stockist_infoWindow.open(stockist_map, marker);
		setStreetView(latlng);		
	}
	else {
		google.maps.event.addListener(marker, 'click', function() {
			stockist_infoWindow.setContent(html);
			stockist_infoWindow.open(stockist_map, marker);
			setStreetView(latlng);
		});
	}
	
	stockist_markers.push(marker);
}

function streetView(lat,lng) {
	var dom = 'streetview';
	panorama = new google.maps.StreetViewPanorama(document.getElementById(dom));
	displayStreetView(lat,lng, dom);
}

function displayStreetView(lat,lng, dom) {
	var latlng = new google.maps.LatLng(lat,lng);
	
	var panoramaOptions = {
	  position: latlng,
	  panControl: true,
	  linksControl: true,
	  enableCloseButton: true,
	  disableDoubleClickZoom: true,
	  addressControl: false,
	  visible: true,
	  pov: {
	    heading: 270,
	    pitch: 0,
	    zoom: 1
	  }
	};
	stockist_panorama = new google.maps.StreetViewPanorama(document.getElementById(dom),panoramaOptions);
	stockist_map.setStreetView(stockist_panorama);
}