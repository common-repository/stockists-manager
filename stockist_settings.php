<?php
define('STOCKIST_SETTINGS', 'stockist_settings');
define('STOCKIST_SETTINGS_details', 'stockist_settings_details');

add_filter( 'plugin_action_links', array('Stockist_settings', 'plugin_action_links'), 10, 2);
add_action( 'admin_menu', array('Stockist_settings', 'config_page_init') );

add_action('admin_print_scripts', array('Stockist_settings','config_page_scripts'));


class Stockist_settings extends Stockist_framework {
	
	function config_page_scripts() {
		//wp_deregister_script('dashboard');
		//wp_enqueue_script('dashboard');
	}
	
	function config_page_init() {
		if (function_exists('add_submenu_page'))
			add_submenu_page('options-general.php', 'Stockist Settings', 'Stockist', 'manage_options', 'stockist-settings', array('Stockist_settings', 'stockist_settings_main'));
	}
	
	function stockist_settings_main() {
		
		$options = get_option(STOCKIST_ADMIN);
		$distance = $options['distance'];
		
		?>
		
		<div class="wrap">
		<div class="metabox-holder">
		
			<br>
			
			<h2>Stockist configuration</h2>
			<hr style="background:#ddd;color:#ddd;height:1px;border:none;">
			<h4>Stockists manager plugin for WooCommerce by <a href="http://berocket.com" target="_blank">BeRocket</a> &amp; <a href="http://dholovnia.me" target="_blank">Dima Holovnia</a></h4>
			<br>
			
			<table width="100%"><tr>
			<td valign="top" width="50%" style="padding-right:10px;">
				
				<?php
				$map_types_tab = array('roadmap'=>'Roadmap', 'satellite'=>'Satellite', 'hybrid'=>'Hybrid', 'terrain'=>'Terrain');
				$distance_tab = array('km'=>'Kilometer', 'miles'=>'Miles');
				
				$criteria['key'] = STOCKIST_SETTINGS;
				$criteria['box_title'] = 'Your Stockist Settings';
				$criteria['fields'][] = array('name'=>'width', 'title'=>'Map width <small>(100% or any value in pixels - Ex: 480px)</small>');
				$criteria['fields'][] = array('name'=>'height', 'title'=>'Map height <small>(Any value in pixels - Ex: 380px)</small>');
				$criteria['fields'][] = array('name'=>'map_type', 'title'=>'Type of Map', 'type'=>'select', 'select_values'=>$map_types_tab);
				$criteria['fields'][] = array('name'=>'distance', 'title'=>'Distance', 'type'=>'select', 'select_values'=>$distance_tab);
				$criteria['fields'][] = array('name'=>'radius', 'title'=>'Search radius');
				$criteria['fields'][] = array('name'=>'zoom', 'title'=>'Zoom level <small>(0 to 20)</small>');
				$criteria['fields'][] = array('name'=>'lat', 'title'=>'Latitude <small>(Default map latitude)</small>');
				$criteria['fields'][] = array('name'=>'lng', 'title'=>'Longitude <small>(Default map longitude)</small>');
				$criteria['fields'][] = array('name'=>'nb_stockists', 'title'=>'Number of stockists to display by page or Map');
				$criteria['fields'][] = array('name'=>'custom_marker', 'title'=>'URL of the custom market to use');
				$criteria['fields'][] = array('name'=>'default_stockists_search', 'title'=>'Check to load the Stockist on the Map by default', 'type'=>'checkbox');
				$criteria['fields'][] = array('name'=>'closest_stockists', 'title'=>'Check to load the closest Stockist by default', 'type'=>'checkbox');
				$criteria['fields'][] = array('name'=>'streetview', 'title'=>'Street view display (as a Map overlay - includes a link in the marker InfoWindow)', 'type'=>'checkbox');
				$criteria['fields'][] = array('name'=>'directions', 'title'=>'Display the directions (in the marker InfoWindow)', 'type'=>'checkbox');
				parent::display_admin_control($criteria);
				?>
			</td>
			
			<td valign="top" style="padding-left:10px;">
				
				<?php
				$criteria3['key'] = STOCKIST_SETTINGS_details;
				$criteria3['box_title'] = 'Display of Stockist details options';
				$criteria3['fields'][] = array('name'=>'zoom', 'title'=>'Map zoom level <small>(0 to 20)</small>');
				$criteria3['fields'][] = array('name'=>'width', 'title'=>'Map and/or Street View width <small>(100% or any value in pixels - Ex: 480px)</small>');
				$criteria3['fields'][] = array('name'=>'height', 'title'=>'Map and/or Street View height <small>(Any value in pixels - Ex: 380px)</small>');
				$criteria3['fields'][] = array('name'=>'streetview', 'title'=>'Display the street view', 'type'=>'checkbox');
				parent::display_admin_control($criteria3);
				?>
				
				<?php
				?>
				
			</td>
			</tr></table>
			
		</div></div>
			
		<?php
	}
	
	function plugin_action_links($links, $file) {
		if ( $file == plugin_basename( dirname(__FILE__).'/stockist.php' ) ) {
			$links[] = '<a href="options-general.php?page=stockist-settings">Settings</a>';
		}
		return $links;
	}
	
	function set_default_values() {
		//app setup
		$criteria['key'] = STOCKIST_SETTINGS;
		$criteria['default_values'] = array('width'=>'100%', 'height'=>'380px', 'map_type'=>'roadmap', 'zoom'=>'5', 'lat'=>'40', 'lng'=>'-100', 
		'nb_stockists'=>'20', 'distance'=>'miles', 'default_stockists_search'=>'on', 'streetview'=>'on');
		parent::set_default_values($criteria);
		//Stockist details
		$criteria3['key'] = STOCKIST_SETTINGS_details;
		$criteria3['default_values'] = array('zoom'=>'16', 'width'=>'100%', 'height'=>'280px');
		parent::set_default_values($criteria3);
	}
	
}

?>