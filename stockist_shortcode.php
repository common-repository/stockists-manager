<?php

class Stockist_shortcode {

	static $js_object;
	static $js_map;

	function Stockist_shortcode() {
		add_shortcode( 'stockist', array(__CLASS__, 'display_stockists') );
		add_action('wp_footer', array(__CLASS__, 'add_scripts'));
	}

	function add_scripts() {
		if(self::$js_map) {
			self::add_js_map();
		}
	}

	function add_js_map() {
		//Google Map API
		wp_register_script('gmap_api', 'http://maps.google.com/maps/api/js?sensor=false', array('jquery'));
		wp_print_scripts('gmap_api');
		//Stockist locator
		wp_register_script('stockist_js', plugin_dir_url( __FILE__ ).'class/stockist_locator/include/js/script.js', array('jquery'));
		wp_print_scripts('stockist_js');
	}

	function js_stockist_declaration($criteria=array()) {
		global $post;

		$display = $criteria['display'];
		$category = $criteria['category'];
		$nb_display = $criteria['nb_display'];

		if(self::$js_object!=1) {
			$options = get_option(STOCKIST_SETTINGS);
			$options2 = get_option(STOCKIST_SETTINGS_details);

			$options3 = get_option(STOCKIST);
			$widget_nb_display = $options3['nb_stockists'];

			echo '<script>
			/* <![CDATA[ */
			var Stockist = {
				"ajaxurl": "'.admin_url('admin-ajax.php').'",
				"plugin_url": "'.plugin_dir_url( __FILE__ ).'",
				"category_id": "'.$category.'",
				"radius_id": "",
				"nb_display": "'.$nb_display.'",
				"widget_nb_display": "'.$widget_nb_display.'",
				"map_type": "'.$options['map_type'].'",
				"zoom": '.(int)$options['zoom'].',
				"lat": '.(int)$options['lat'].',
				"lng": '.(int)$options['lng'].',
				"current_lat": "",
				"current_lng": "",
				"searched_lat": "",
				"searched_lng": "",
				"custom_marker": "'.$options['custom_marker'].'",
				"search": "'.$options['default_stockists_search'].'",
				"closest_stockists": "'.$options['closest_stockists'].'",
				"zoom_detail": '.(int)$options2['zoom'].',
				"streetview": "'.$options2['streetview'].'",
				"product_slug": "'.$post->ID.'"
			};
			/* ]]> */
			</script>';
		}

		self::$js_object=1;
	}

	function display_stockists($atts, $content = null, $code = null) {
		extract(shortcode_atts(array(
			'display' => '',
			'category' => '',
			'nb_display' => '',
			'category_filter' => '',
			'distance_filter' => ''
		), $atts));

		self::js_stockist_declaration(array('category'=>$category, 'nb_display'=>$nb_display));

		$content .= '<style type="text/css">#map img { max-width: none; }</style>';

		self::$js_map = true;

		//display stockist details
		if($_GET['stockist_id']>0) {

			$sdb1 = new Stockist_db();
			$stockist = $sdb1->return_stockists(array('id'=>$_GET['stockist_id']));

			$sb1 = new Stockist_display();
			$stockist_details = $sb1->get_stockist_details_display($stockist);
			$content = $stockist_details;

			$id = $stockist[0]['id'];
			$name = $stockist[0]['name'];
			$logo = $stockist[0]['logo'];
			$address = $stockist[0]['address'];
			$url = $stockist[0]['url'];
			$marker_icon = $stockist[0]['marker_icon'];

			$options = get_option(STOCKIST_SETTINGS);

			//get infowindow display
			$sd = new Stockist_display();
			$marker_text = $sd->getMarkerInfowindowDisplay(array('id'=>$id, 'name'=>$name, 'logo'=>$logo, 'address'=>$address, 'url'=>$url, 'streetview'=>$options['streetview'], 'directions'=>$options['directions']));

			$GLOBALS['stockist_js_on_ready'] = 'init_basic_map(\''.$stockist[0]['lat'].'\',\''.$stockist[0]['lng'].'\', \''.addslashes($marker_text).'\', \''.$marker_icon.'\');';
		}

		//display stockists (map or list)
		else {

			if($_GET['address']!='') {
				$GLOBALS['stockist_js_on_ready'] = "stockist_setAddress('".$_GET['address']."', '$display');";
			}
			else {
				if($display=='list') $GLOBALS['stockist_js_on_ready'] .= 'stockist_load("list");';
				elseif($display=='both') $GLOBALS['stockist_js_on_ready'] .= 'stockist_load("both");';
				else $GLOBALS['stockist_js_on_ready'] .= 'stockist_load("map");';
			}

			//search box
			//~ $search_box = self::displayAddressSearchBox(array('category_filter'=>$category_filter, 'distance_filter'=>$distance_filter));
			//~ $content .= $search_box;

			$sdb1 = new Stockist_db();
			$nb_stockists_tab = $sdb1->return_nb_stockists(array('category_id'=>$category));
			$nb_stockists = $nb_stockists_tab['nb'];

			//number of stockist & previous/next buttons
			//~ $content .= '<div style="width:100%; padding-bottom:5px; border-bottom: 1px solid #e7e7e7; margin-bottom:10px;">';
			//~
			//~ $content .= '<span id="stockist_title">';
			//~ if($nb_stockists==1) $content .= $nb_stockists.' '.$GLOBALS['stockist_lang']['stockist'];
			//~ elseif ($nb_stockists>1) $content .= $nb_stockists.' '.$GLOBALS['stockist_lang']['stockists'];
			//~ $content .= '</span>';
			//~
			//~ $content .= '<div style="float:right;" id="previousNextButtons"></div>';
			//~
			//~ $content .= '</div>';

			if($display=='list') {
				$content .= self::get_stockists_display_list();
			}
			elseif($display=='both') {
				$content .= self::get_stockists_display_map_list();
			}
			else {
				$content .= self::get_stockists_display_map();
			}
		}

		$content = '<p>'.$content.'</p>';
		return $content;
	}

	/*
	More display
	*/

	function displayPreviousNextButtons($page_number, $nb_stockists, $nb_display) {
		if($page_number>1) {
			$display .= '<a href="#" id="stockist_previous">'.$GLOBALS['stockist_lang']['previous'].'</a> ';
			$display .= ' - <b>'.$page_number.'</b>';
			$previous_flag=1;
		}
		if($nb_stockists>($nb_display*$page_number)) {
			if($previous_flag==1) $display .= ' - ';
			$display .= '<a href="#" id="stockist_next">'.$GLOBALS['stockist_lang']['next'].'</a>';
		}
		return $display;
	}

	function displayMarkersContent($locations) {

		$options = get_option(STOCKIST_SETTINGS);

		$sd = new Stockist_display();

		for($i=0; $i<count($locations);$i++) {
			$id = $locations[$i]['id'];
			$name = $locations[$i]['name'];
			$logo = $locations[$i]['logo'];
			$address = $locations[$i]['address'];
			$url = $locations[$i]['url'];

			$markers[$i] .= $sd->getMarkerInfowindowDisplay(array('id'=>$id, 'name'=>$name, 'logo'=>$logo, 'address'=>$address, 'url'=>$url, 'streetview'=>$options['streetview'], 'directions'=>$options['directions'], 'more_details'=>1));
		}
		return $markers;
	}

	function format_marker_content() {

	}

	/*
	Start display functions
	*/

	function displayAddressSearchBox($criteria=array()) {
		$category_filter = $criteria['category_filter'];
		$distance_filter = $criteria['distance_filter'];

		$options = get_option(STOCKIST_SETTINGS);
		$distance = $options['distance'];

		$display = '<div>'.$GLOBALS['stockist_lang']['search_by_address'].'</div>';

		$display .= '<form method="GET">';
		$display .= '<input type="text" id="stockist_address" name="stockist_address" style="width:440px;" value="'.$_GET['address'].'" />';
		$display .= ' <input type="submit" id="stockist_search_btn" value="'.$GLOBALS['stockist_lang']['search'].'" style="padding:2px;"/>';

		if($category_filter==1 || $distance_filter==1) {

			$display .= '<div style="margin-bottom:20px; margin-top:10px;">';

			if($category_filter==1) {
				$db1 = new Stockist_db();
				$categories = $db1->return_categories();

				$nb_stockists_by_cat = $db1->return_nb_stockists_by_category();

				$display .= $GLOBALS['stockist_lang']['category'].': ';
				$display .= '<select id="stockist_category_filter">';
				$display .= '<option value="">'.$GLOBALS['stockist_lang']['all_categories'].'</option>';
				for($i=0; $i<count($categories); $i++) {

					$nb_stockists = $nb_stockists_by_cat[$categories[$i]['id']];
					if($nb_stockists=='') $nb_stockists=0;

					$display .= '<option value="'.$categories[$i]['id'].'">'.$categories[$i]['name'].' ('.$nb_stockists.')</option>';
				}
				$display .= '</select>';
			}

			if($distance_filter==1) {

				$display .= '&nbsp;&nbsp;&nbsp;'.$GLOBALS['stockist_lang']['distance'].': ';
				$display .= '<select id="stockist_distance_filter">';
				$display .= '<option value=""></option>';
				for($i=0; $i<count($GLOBALS['stockist_settings']['distance']); $i++) {
					$display .= '<option value="'.$GLOBALS['stockist_settings']['distance'][$i].'">'.$GLOBALS['stockist_settings']['distance'][$i].' '.$distance.'</option>';
				}
				$display .= '</select>';
			}

			$display .= '</div>';
		}

		$display .= '</form>';

		return $display;
	}

	function get_stockists_display_map() {
		$options = get_option(STOCKIST_SETTINGS);
		$width = $options['width'];
		$height = $options['height'];
		$content .= '<div id="map" style="overflow: hidden; width:'.$width.'; height:'.$height.';"></div>';
		return $content;
	}

	function get_stockists_display_list() {
		$content .= '<div id="stockist_list"></div>';
		return $content;
	}

	function get_stockists_display_map_list() {
		$options = get_option(STOCKLIST_SETTINGS);
		$width = $options['width'];
		$height = $options['height'];
		$content .= '<div id="map" style="overflow: hidden; width:'.$width.'; height:'.$height.';"></div>';
		$content .= '<br>';
		$content .= '<div id="stockist_list"></div>';
		$content .= '<div id="previousNextButtons2"></div>';
		return $content;
	}
}

new Stockist_shortcode();

?>