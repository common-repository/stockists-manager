<?php
/*
	Plugin Name: Stockists Manager for Woocommerce
	Plugin URI: http://berocket.com/wp-plugins/stockist
	Description: Integrate an Advanced and fully featured Stockists Manager into your WordPress
	Version: 1.0.2.1
	Author: BeRocket
	Author URI: http://berocket.com
*/

require_once dirname( __FILE__ ).'/class/stockist_framework/Stockist_framework.php';
require_once dirname( __FILE__ ).'/stockist_settings.php';
require_once dirname( __FILE__ ).'/stockist_admin.php';
require_once dirname( __FILE__ ).'/stockist_db.php';
require_once dirname( __FILE__ ).'/stockist_shortcode.php';
require_once dirname( __FILE__ ).'/stockist_display.php';
require_once dirname( __FILE__ ).'/stockist_widget.php';


$GLOBALS['stockist_lang']['stockist'] = 'stockist';
$GLOBALS['stockist_lang']['stockists'] = 'stockists';
$GLOBALS['stockist_lang']['stockist_name'] = 'Stockist name';
$GLOBALS['stockist_lang']['address'] = 'Address';
$GLOBALS['stockist_lang']['url'] = 'Url';
$GLOBALS['stockist_lang']['tel'] = 'Tel';
$GLOBALS['stockist_lang']['email'] = 'Email';
$GLOBALS['stockist_lang']['description'] = 'Description';
$GLOBALS['stockist_lang']['more_information'] = 'More information';
$GLOBALS['stockist_lang']['more_details'] = 'More details';
$GLOBALS['stockist_lang']['get_directions'] = 'Get directions';
$GLOBALS['stockist_lang']['to_here'] = 'To here';
$GLOBALS['stockist_lang']['from_here'] = 'From here';
$GLOBALS['stockist_lang']['streetview'] = 'Streetview';
$GLOBALS['stockist_lang']['view_all_stockists'] = 'View all stockists';
$GLOBALS['stockist_lang']['next'] = 'Next';
$GLOBALS['stockist_lang']['previous'] = 'Previous';
$GLOBALS['stockist_lang']['search_by_address'] = 'Search by address';
$GLOBALS['stockist_lang']['search'] = 'Search';
$GLOBALS['stockist_lang']['distance'] = 'Distance';
$GLOBALS['stockist_lang']['category'] = 'Category';
$GLOBALS['stockist_lang']['all_categories'] = 'All categories';
$GLOBALS['stockist_lang']['gallery'] = 'Photo Gallery';

$GLOBALS['stockist_settings']['distance'] = array('1', '5', '10', '25', '50', '100');

class Stockist {
	
	function Stockist() {
		add_action('wp_footer', array(__CLASS__, 'add_onload'));
		
		//AJAX
		add_action( 'wp_ajax_nopriv_stockist_listener', array(__CLASS__, 'stockist_listener') );
		add_action( 'wp_ajax_stockist_listener', array(__CLASS__, 'stockist_listener') );
		
		add_action( 'wp_ajax_nopriv_stockist_add_category', array(__CLASS__, 'stockist_ajax_add_category') );
		add_action( 'wp_ajax_stockist_add_category', array(__CLASS__, 'stockist_ajax_add_category') );
		
		add_action( 'woocommerce_product_data_panels', array($this, 'stockist_woocommerce_product_data_panels') );
		add_filter( 'woocommerce_product_data_tabs', array($this, 'stockist_display_woocommerce_tabs'), 100 );
		add_filter( 'product_type_options', array($this, 'stockist_product_type_options'), 100 );
		
		add_action( 'woocommerce_process_product_meta_simple', array(__CLASS__, 'stockist_product_save_data'), 10 );
		
		add_filter( 'woocommerce_product_tabs', array($this, 'stockist_woocommerce_product_tabs'), 100 );
		
		if(is_admin()) {
			register_activation_hook(__FILE__, array(__CLASS__, 'on_plugin_activation'));
		}
	}
	
	function stockist_product_type_options( $options ){
		$options['stockists'] = array(
			'id'            => '_stockists',
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'Stockists', 'woocommerce' ),
			'description'   => __( 'Products are selling through stockists', 'woocommerce' ),
			'default'       => 'no'
		);
		return $options;
	}
	
	function stockist_display_woocommerce_tabs( $tabs ){
		$tabs['inventory'] = array(
			'label'  => __( 'Inventory', 'woocommerce' ),
			'target' => 'inventory_product_data',
			'class'  => array( 'show_if_simple', 'show_if_variable', 'show_if_grouped', 'hide_if_stockists' ),
		);
		$tabs['shipping'] = array(
			'label'  => __( 'Shipping', 'woocommerce' ),
			'target' => 'shipping_product_data',
			'class'  => array( 'hide_if_virtual', 'hide_if_grouped', 'hide_if_external', 'hide_if_stockists' ),
		);
		$general = array_shift($tabs);
		$stockists = array(
			'label'  => __( 'Stockists', 'woocommerce' ),
			'target' => 'stockists_product_data',
			'class'  => array( 'show_if_stockists', 'stockists_options' ),
		);
		array_unshift($tabs, $general, $stockists);
        return $tabs;
	}
	
	function stockist_woocommerce_product_tabs( $tabs ){
		$description = array_shift($tabs);
		$stockists = array(
			'title'  => __( 'Stockists', 'woocommerce' ),
			'priority' => '11',
			'callback'  => array($this, 'woocommerce_product_stockists_tab'),
		);
		array_unshift($tabs, $stockists, $description);
		return $tabs;
	}
	
	function woocommerce_product_stockists_tab() {
		global $woocommerce, $post;
		$category	= get_post_meta( $post->ID, '_stockists_category' );
		$options	= get_option( STOCKIST_SETTINGS );
		$s1 = new Stockist_shortcode();
		
		if( $options['default_stockists_search'] == 'on' )
			$heading = esc_html( apply_filters( 'woocommerce_product_description_heading', __( 'Stockists on map', 'woocommerce' ) ) );
		else
			$heading = esc_html( apply_filters( 'woocommerce_product_description_heading', __( 'Stockists list', 'woocommerce' ) ) );
		?>

		<h2><?php echo $heading; ?></h2>
		<div>
			<?
			if( $options['default_stockists_search'] == 'on' or $_GET['stockist_id'] > 0 ){
				echo $s1->display_stockists( array( "display" => "map", "category" => $category[0] ) );
			}else{
				$s1->js_stockist_declaration( array( "category" => $category[0], "display" => "list" ) );
				if( $options['closest_stockists'] == 'on' ){
					echo '<p id="widget_stockist_list"></p>';
					$GLOBALS['stockist_js_on_ready'] .= 'display_widget_closest_stockists();';
				}else{
					echo '<p id="stockist_list"></p>';
					$GLOBALS['stockist_js_on_ready'] .= 'init_stockists_list();';
				}
			}
			?>
		</div>
		<?
		
		//include the JS files
		add_action('wp_footer', array('Stockist_shortcode', 'add_js_map'));
	}
	
	function stockist_woocommerce_product_data_panels(){
		$sdb1 = new Stockist_db();
		$categories = $sdb1->return_categories();
		
		foreach( $categories as $category )
			$ocategory[$category['id']] = $category['name'];
		?>
			<div id="stockists_product_data" class="panel woocommerce_options_panel">

				<?php

				echo '<div class="options_group">';

				// Stock status
				woocommerce_wp_select( array( 'id' => '_stockists_category', 'label' => __( 'Stockists category<br /><a href="#" id="add_new_category_action">(add new category)</a>', 'woocommerce' ), 'options' => $ocategory ) );

				do_action('woocommerce_product_options_stockists_status');
				
				echo '</div>';

				do_action( 'woocommerce_product_options_stockists_product_data' );
				?>

			</div>
		<?
	}
	
	function stockist_product_save_data( $post_id ) {
		
		if ( ! empty( $_POST['_stockists'] ) ) {
			update_post_meta( $post_id, '_stockists', 'yes' );
			update_post_meta( $post_id, '_stockists_category', wc_clean( $_POST['_stockists_category'] ) );
			
			update_post_meta( $post_id, '_manage_stock', 'no' );
			update_post_meta( $post_id, '_backorders', wc_clean( $_POST['_backorders'] ) );
			update_post_meta( $post_id, '_stock', '' );
			
			wc_update_product_stock_status( $post_id, wc_clean( $_POST['_stock_status'] ) );
		} else {
			update_post_meta( $post_id, '_stockists', 'no' );
			update_post_meta( $post_id, '_stockists_category', '0' );
		}
	}
	
	function add_onload() {
	    ?>
	    <script type="text/javascript">
	    my_onload_callback = function() {
	    	<?php echo $GLOBALS['stockist_js_on_ready']; ?>
	    };
		
	    if( typeof jQuery == "function" ) { 
	        jQuery(my_onload_callback); // document.ready
	    }
	    else {
	        document.getElementsByTagName('body')[0].onload = my_onload_callback; // body.onload
	    }
	    
	    </script>
	    
	    <?php
	}
	
	//AJAX calls
	function stockist_listener() {
		
		$method = $_POST['method'];
		
		//display stockists Map
		if($method=='display_map') {
			$lat = $_POST['lat'];
			$lng = $_POST['lng'];
			$page_number = $_POST['page_number'];
			$category_id = $_POST['category_id'];
			$radius_id = $_POST['radius_id'];
			$nb_display = $_POST['nb_display'];
			
			$sdb1 = new Stockist_db();
			$ss1 = new Stockist_shortcode();
			
			$options = get_option(STOCKIST_SETTINGS);
			if( $radius_id <= 0 and $options['radius'] ){
				$radius_id = $options['radius'];
			}
			if($nb_display=='') $nb_display = $options['nb_stockists'];
			$distance_unit = $options['distance'];
			
			if($page_number=='') $page_number = 1; //default value just in case
			if($nb_display=='') $nb_display = 20; //default value just in case
			
			$locations =  $sdb1->get_locations(array('lat'=>$lat, 'lng'=>$lng, 'page_number'=>$page_number, 'nb_display'=>$nb_display, 
			'distance_unit'=>$distance_unit, 'category_id'=>$category_id, 'radius_id'=>$radius_id));
			
			//calculate number total of stockists
			$stockists2 =  $sdb1->get_locations(array('lat'=>$lat, 'lng'=>$lng,
			'distance_unit'=>$distance_unit, 'category_id'=>$category_id, 'radius_id'=>$radius_id));
			$nb_stockists = count($stockists2);
			
			//previous/next buttons
			$previousNextButtons = $ss1->displayPreviousNextButtons($page_number, $nb_stockists, $nb_display);
			
			if($nb_stockists==1) $title = $nb_stockists.' '.$GLOBALS['stockist_lang']['stockist'];
			else $title = $nb_stockists.' '.$GLOBALS['stockist_lang']['stockists'];

			$results['title'] = $title;
			$results['previousNextButtons'] = $previousNextButtons;
			$results['locations'] = $locations;
			$results['markersContent'] = $ss1->displayMarkersContent($locations);
			$results = json_encode($results);
			
			echo $results;
			exit;
		}
		
		//display stockists list
		else if($method=='display_list') {
			$page_number = $_POST['page_number'];
			$lat = $_POST['lat'];
			$lng = $_POST['lng'];
			$category_id = $_POST['category_id'];
			$radius_id = $_POST['radius_id'];
			$nb_display = $_POST['nb_display'];
			$no_info_links = $_POST['no_info_links']; //activate or no the links display
			$widget_display = $_POST['widget_display'];
			$display_type = $_POST['display_type'];
			
			$sdb1 = new Stockist_db();
			$ss1 = new Stockist_shortcode();
			
			$options = get_option(STOCKIST_SETTINGS);
			if( $radius_id <= 0 and $options['radius'] ){
				$radius_id = $options['radius'];
			}
			if($nb_display=='') $nb_display = $options['nb_stockists'];
			$distance_unit = $options['distance'];
			
			if($page_number=='') $page_number = 1; //default value just in case
			if($nb_display=='') $nb_display = 20; //default value just in case
			
			$stockists =  $sdb1->get_locations(array('lat'=>$lat, 'lng'=>$lng, 'page_number'=>$page_number, 'nb_display'=>$nb_display, 
			'distance_unit'=>$distance_unit, 'category_id'=>$category_id, 'radius_id'=>$radius_id));
			
			//calculate number total of stockists
			$stockists2 =  $sdb1->get_locations(array('lat'=>$lat, 'lng'=>$lng, 
			'distance_unit'=>$distance_unit, 'category_id'=>$category_id, 'radius_id'=>$radius_id));
			$nb_stockists = count($stockists2);
			
			//previous/next buttons
			$previousNextButtons = $ss1->displayPreviousNextButtons($page_number, $nb_stockists, $nb_display);
			
			if($lat!=''&&$lng!='') $distance_display=1;
			
			$sd1 = new Stockist_display();
			if($nb_stockists>0) {
				if($display_type=='both') $content = $sd1->display_stockists_list($stockists, array('distance_display'=>$distance_display, 'no_info_links'=>$no_info_links, 'widget_display'=>$widget_display));
				else $content = $sd1->display_stockists_list($stockists, array('distance_display'=>$distance_display, 'no_info_links'=>$no_info_links, 'widget_display'=>$widget_display));
			}
			else $content = __('No stockists');

			if($nb_stockists==1) $title = $nb_stockists.' '.$GLOBALS['stockist_lang']['stockist'];
			else $title = $nb_stockists.' '.$GLOBALS['stockist_lang']['stockists'];
			
			$results['title'] = $title;
			$results['previousNextButtons'] = $previousNextButtons;
			$results['stockists'] = $content;
			$results = json_encode($results);
			
			echo $results;
			exit;
		}
	}
	
	function stockist_ajax_add_category(){
		$category_name = $_POST['category_name'];
		if( is_admin() and $category_name ){
			$sdb1 = new Stockist_db();
			if( !$sdb1->category_exist( $category_name ) ){
				$sdb1->add_category( array( "name" => $category_name ) );
				$categories = $sdb1->return_categories();
				$return = '';
				for($i=0; $i<count($categories);$i++) {
					if(in_array($categories[$i]['id'],$stockist[0]['category'])) $return .= '<option value="'.$categories[$i]['id'].'" selected>'.$categories[$i]['name'].'</option>';
					else $return .= '<option value="'.$categories[$i]['id'].'">'.$categories[$i]['name'].'</option>';
				}
				echo $return;
				exit;
			}
			exit;
		}
		exit;
	}
	
	function on_plugin_activation() {
		if(self::notify_verification()) {
			//create the plugin table if it doesn't exist
			$sdb1 = new Stockist_db();
			$sdb1->setup_tables();
			//set default settings
			$ss1 = new Stockist_settings();
			$ss1->set_default_values();		
		}
	}
	
	function notify_verification() {
		return 1;
	}
}

new Stockist();

?>