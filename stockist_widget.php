<?php

define('STOCKIST', 'stockist');

// register the widgets
add_action("plugins_loaded",array('Stockist_widget', 'widget_registration'));

class Stockist_widget extends Stockist_framework {
	
	function widget_registration() {
		//stockists list
		wp_register_sidebar_widget(STOCKIST, 'Stockist', array(__CLASS__, 'widget_display_stockists_list'));
		wp_register_widget_control(STOCKIST, 'Stockist', array(__CLASS__, 'widget_control_stockists_list'));
	}
	
	/*
	Widgets front display
	*/
	
	function widget_display_stockists_list() {
		// show stockists only on product page
		if( is_product() ) {
			$options = get_option( STOCKIST );

			//include the JS files
			add_action( 'wp_footer', array( 'Stockist_shortcode', 'add_js_map' ) );

			$s1 = new Stockist_shortcode();
			$s1->js_stockist_declaration();

			//execute on dom ready
			$GLOBALS['stockist_js_on_ready'] .= 'display_widget_closest_stockists();';

			if ( $options['title'] != '' ) {
				echo '<h3 class="widget-title" style="margin-bottom:5px;">' . $options['title'] . '</h3>';
			}
			echo '<p id="widget_stockist_list"></p>';
		}
	}
	
	/*
	Widgets controls
	*/
	
	function widget_control_stockists_list() {
		$options = get_option( STOCKIST_SETTINGS );

		$criteria['key'] = STOCKIST;
		$criteria['fields'][] = array('name'=>'title', 'title'=>'Widget title:');
		$criteria['fields'][] = array('name'=>'nb_stockists', 'title'=>'Number of stockists:');
		parent::display_widget_control($criteria);
	}
}

new Stockist_widget();

?>