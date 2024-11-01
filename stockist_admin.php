<?php
define('STOCKIST_ADMIN', 'stockist_admin');

class Stockist_admin extends Stockist_framework {
	
	function Stockist_admin() {
		
		add_action( 'admin_menu', array(__CLASS__, 'config_page_init') );
		add_action('admin_print_scripts', array(__CLASS__, 'config_page_scripts'));
		
		if(is_admin()) {
			wp_enqueue_script('gmap_api', 'http://maps.google.com/maps/api/js?sensor=false', array('jquery'));
			wp_enqueue_script( 'stockist_js', plugin_dir_url( __FILE__ ).'class/stockist_locator/include/js/script.js');
			wp_enqueue_script( 'stockist_main_js', plugin_dir_url( __FILE__ ).'class/stockist_locator/include/js/stockist.js');
			wp_enqueue_style('stockist_css', plugin_dir_url( __FILE__ ).'css/style.css');
			add_action( 'admin_enqueue_scripts', function (){
				if(function_exists( 'wp_enqueue_media' )){
					wp_enqueue_media();
				}else{
					wp_enqueue_style('thickbox');
					wp_enqueue_script('media-upload');
					wp_enqueue_script('thickbox');
				}
			});
		}
	}
	
	function config_page_scripts() {
		echo '
			<script>
				/* <![CDATA[ */
				var stockist_ajaxurl = "'.admin_url('admin-ajax.php').'";
				var stockist_id = "'.$_GET['id'].'";';
		if( $_GET['id'] ){
			$sl1 = new Stockist_db();
			$stockist = $sl1->return_stockists(array('id'=>$_GET['id']));
			$gallery = (array) json_decode( $stockist[0]['gallery'] );
			
			echo '
				var imageArray = '.json_encode($gallery['ids']).'
				';
		}
		echo '
				/* ]]> */
			</script>';
	}
	
	function config_page_init() {
		if (function_exists('add_submenu_page')) {
			add_menu_page( 'Stockists', 'Stockists', 'manage_options', 'stockist', array(__CLASS__, 'stockist_list'), '', 7 );
			add_submenu_page('stockist', 'Add new', 'Add new', 'manage_options', 'stockist-add', array(__CLASS__, 'stockist_add'));
			add_submenu_page('stockist', 'Categories', 'Categories', 'manage_options', 'stockist-categories', array(__CLASS__, 'stockist_categories'));
			add_submenu_page('', '', '', 'manage_options', 'stockist-update-location', array(__CLASS__, 'stockist_update_location'));
			add_submenu_page('', '', '', 'manage_options', 'stockist-edit', array(__CLASS__, 'stockist_edit'));
			add_submenu_page('', '', '', 'manage_options', 'stockist-delete', array(__CLASS__, 'stockist_delete_location'));
			add_submenu_page('', '', '', 'manage_options', 'stockist-category-edit', array(__CLASS__, 'display_category_edit'));
			add_submenu_page('', '', '', 'manage_options', 'stockist-category-delete', array(__CLASS__, 'display_category_delete'));
		}
	}
	
	function stockist_categories() {
		?>
		<div class="wrap">
		<div class="metabox-holder">
		<br>
		<?php
		
		$sl1 = new Stockist_db();
		
		if(isset($_POST['add'])) {
			$sl1->add_category(array('name'=>$_POST['name'], 'marker_icon'=>$_POST['marker_icon']));
		}
		
		$categories = $sl1->return_categories();
		$stockistsByCat = $sl1->return_nb_stockists_by_category();
		
		echo '<h2>Stockists Categories</h2>';
		echo '<hr style="background:#ddd;color:#ddd;height:1px;border:none;">';
		
		for($i=0; $i<count($categories); $i++) {
			if($stockistsByCat[$categories[$i]['id']]>0) $nb=$stockistsByCat[$categories[$i]['id']];
			else $nb=0;
			echo '<table width="100%" style="padding-bottom:10px; margin-bottom:10px; border-bottom: 1px solid #e7e7e7;"><tr>';
			echo '<td>';
			echo '<b>'.$categories[$i]['name'].'</b> (Stockists: '.$nb.' - Category id: '.$categories[$i]['id'].')';
			echo '</td>';
			echo '<td align="right">';
			echo '<a href="./admin.php?page=stockist-category-edit&id='.$categories[$i]['id'].'">Edit</a> - ';
			echo '<a href="./admin.php?page=stockist-category-delete&id='.$categories[$i]['id'].'">Delete</a>';
			echo '</td>';
			echo '</tr></table>';
		}
		
		if(count($categories)==0) echo '<br>You don\'t have any category yet.';
		
		echo '<form method="post">';
			echo '<h2>Add a new category</h2>';
			echo '<p>';
			echo 'Name: <input class="widefat" name="name" style="width:360px; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
			echo 'Marker icon URL: <input class="widefat" name="marker_icon" style="width:360px; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
			echo '<p><input class="button-primary" type="submit" name="add" value="Add"></p>';
		echo '</form>';
		
		?>
		</div></div>
		<?php
	}
	
	function display_category_edit() {
		
		?>
		<div class="wrap">
		<div class="metabox-holder">
		<br>
		<?php
		
		$sl1 = new Stockist_db();
		
		if(isset($_POST['edit'])) {
			$sl1->update_category(array('name'=>$_POST['name'], 'marker_icon'=>$_POST['marker_icon'], 'id'=>$_POST['id']));
			
			echo '<script>';
			echo 'window.location = "./admin.php?page=stockist-categories"';
			echo '</script>';
		}
		
		else {
			$categories = $sl1->return_categories(array('id'=>$_GET['id']));
			
			echo '<h2>Edit a category</h2>';
			echo '<hr style="background:#ddd;color:#ddd;height:1px;border:none;">';
			
			echo '<form method="post">';
				echo '<input type="hidden" name="id" value="'.$categories[0]['id'].'">';
				echo '<p>';
				echo 'Name: <input class="widefat" name="name" value="'.$categories[0]['name'].'" style="width:360px; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
				echo 'Marker icon URL: <input class="widefat" name="marker_icon" value="'.$categories[0]['marker_icon'].'" style="width:360px; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
				echo '<p><input class="button-primary" type="submit" name="edit" value="Edit"></p>';
			echo '</form>';
		}
		
		?>
		</div></div>
		<?php
	}
	
	function display_category_delete() {
		?>
		<div class="wrap">
		<div class="metabox-holder">
		<br>
		<?php
		echo '<h2>Delete a category</h2>';
		echo '<hr style="background:#ddd;color:#ddd;height:1px;border:none;">';
		
		$sl1 = new Stockist_db();
		
		if($_GET['confirm']==1) {
			$s1 = new Stockist_db();
			$display = $s1->delete_category($_GET['id']);
			
			echo '<script>';
			echo 'window.location = "./admin.php?page=stockist-categories"';
			echo '</script>';
		} else {
			$categories = $sl1->return_categories(array('id'=>$_GET['id']));
			
			echo '<p><b>Name:</b> '.$categories[0]['name'].'</p>';
			echo '<p>Are you sure you want to delete this category?</p>';
			echo '<a href="./admin.php?page=stockist-category-delete&id='.$_GET['id'].'&confirm=1">Yes, delete this category</a> - <a href="./admin.php?page=stockist-categories">Cancel</a>';
		}
		
		?>
		</div></div>
		<?php
	}
	
	function stockist_list() {
		?>
		
		<div class="wrap">
		<div class="metabox-holder">
		<br>
		
		<?php
		$sl1 = new Stockist_db();
		$stockists = $sl1->return_stockists();
		
		echo '<h2>Stockists list <font size="-1">';
		if(count($stockists)>0) echo '(<a href="./admin.php?page=stockist-add">Add stockist</a>)</font>';
		echo '</h2>';
		echo '<hr style="background:#ddd;color:#ddd;height:1px;border:none;">';
		
		//get categories list (id + name)
		$categories = $sl1->return_categories();
		for($i=0; $i<count($categories); $i++) {
			$categories_list[$categories[$i]['id']] = $categories[$i]['name'];
		}
		
		for($i=0; $i<count($stockists); $i++) {
			$latLng = 'Lat: '.$stockists[$i]['lat'].', Lng: '.$stockists[$i]['lng'];
			
			echo '<table width="100%" style="padding-bottom:10px; margin-bottom:10px; border-bottom: 1px solid #e7e7e7;"><tr>';
			echo '<td>';
			echo '<h2>'.$stockists[$i]['name'].' ';
			if(count($stockists[$i]['category'])>0){
				echo '<small><font size="-1">';
				$cats = '';
				foreach( $stockists[$i]['category'] as $stock_cat )
					$cats .= '(<span style="color:#921414">'.$categories_list[$stock_cat].'</span>), ';
				echo rtrim( $cats, ', ' );
				echo '</font></small>';
			}
			
			if( $stockists[$i]['premium'] ) echo "<span class='premium_stockist'>PREMIUM</span>";
			
			echo '</h2>';
			echo ''.$stockists[$i]['address'];
			echo ' <small><font color="blue">('.$latLng.')</font></small>';
			echo '</td>';
			echo '<td align="right">';
			echo '<a href="./admin.php?page=stockist-edit&id='.$stockists[$i]['id'].'">Edit</a> - ';
			echo '<a href="./admin.php?page=stockist-delete&id='.$stockists[$i]['id'].'">Delete</a>';
			echo '</td>';
			echo '</tr></table>';
		}
		
		if(count($stockists)==0) echo '<br>You don\'t have any stockists yet: <a href="./admin.php?page=stockist-add">Add one now?</a>';
		
		?>
		</div></div>
		<?php
	}
	
	function stockist_delete_location() {
		$id = $_GET['id'];
		
		?>
		
		<div class="wrap">
		<div class="metabox-holder">
		
			<h2>Delete a stockist</h2>
			<hr style="background:#ddd;color:#ddd;height:1px;border:none;">
			
			<?php
			if(isset($_GET['confirm'])) {
				$s1 = new Stockist_db();
				$display = $s1->delete_stockist($id);
				echo '<p>'.$display.'<p>';
				echo '<a href="./admin.php?page=stockist">Stockists list</a>';
			}
			else {
				$s1 = new Stockist_db();
				$stockist = $s1->return_stockists(array('id'=>$id));
				echo '<p><b>Name:</b> '.$stockist[0]['name'].'</p>';
				echo '<p>Are you sure you want to delete this stockist?</p>';
				echo '<a href="./admin.php?page=stockist-delete&id='.$id.'&confirm=1">Yes, delete this stockist</a> - <a href="./admin.php?page=stockist">Cancel</a>';
			}
			
			?>
			
		</div></div>
		<?php
	}
	
	function stockist_update_location() {
		$id = $_GET['id'];
		?>
		
		<div class="wrap">
		<div class="metabox-holder">
		
			<h2>Edit an address</h2>
			<hr style="background:#ddd;color:#ddd;height:1px;border:none;">
			
			<?php
			$sl1 = new Stockist_db();
			$stockist = $sl1->return_stockists(array('id'=>$_GET['id']));
			
			echo '<p><b>Stockist name</b>: '.$stockist[0]['name'].'</p>';
			
			if(isset($_POST['add'])) {
				
				$lat = $geocode['lat'];
				$lng = $geocode['lng'];
								
				$map_url = 'http://maps.google.com/maps/api/staticmap?center='.$lat.','.$lng.'&zoom=15&size=400x250&markers=color:red|'.$lat.','.$lng.'&sensor=false';
				
				echo '<table><tr><td><img src="'.$map_url.'" style="padding-right:20px;"></td>';
				echo '<td valign="top"><b>Address:</b><br>'.$_POST['address'].'</td></tr></table>';
				
				if($lng!='' && $lng!='') {
					echo '<br><form method="post">';
					echo '<input type="hidden" name="lat" value="'.$lat.'">';
					echo '<input type="hidden" name="lng" value="'.$lng.'">';
					echo '<input type="hidden" name="address" value="'.$_POST['address'].'">';
					echo '<p class="submit" style="padding-bottom:0px; padding-top:0px;">';
					echo '<input class="button-primary" type="submit" name="save" value="Save this location">';
					echo '</p>';
					echo '</form>';
				}
				else {
					echo '<br>We couldn\'t geocode this address. Please make sure a zip code and a country has been specified, and that no other information than an address related data has been added (no P.O box etc).<br>';
					echo '<a href="javascript:history.go(-1)">Try again</a>';
				}
				
			}
			else {
				echo '<form method="post">';
					echo '<p><label>Address <small>(Full address, including the zip code and country)</small></label></p>';
					echo '<p><input class="widefat" type="text" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"
					name="address" value="'.$stockist[0]['address'].'"></p>';
					
					echo '<p class="submit" style="padding-bottom:0px; padding-top:0px;">';
					echo '<input class="button-primary" type="submit" name="add" value="Geocode and preview this address">';
					echo '</p>';
				echo '</form>';
			}
			
			?>
			
		</div></div>
		
		<?php
	}
	
	function stockist_edit() {
		?>
		
		<div class="wrap">
		<div class="metabox-holder">
		
			<h2>Edit Stockist</h2>
			<hr style="background:#ddd;color:#ddd;height:1px;border:none;">
			
			<?php
			$sl1 = new Stockist_db();
			$stockist = $sl1->return_stockists(array('id'=>$_GET['id']));
			
			if(isset($_POST['update'])) {
				$criteria['id'] = $_GET['id'];
				$criteria['category'] = $_POST['category'];
				$criteria['address'] = $_POST['address'];
				$criteria['lat'] = $_POST['lat'];
				$criteria['lng'] = $_POST['lng'];
				$criteria['name'] = $_POST['name'];
				$criteria['logo'] = $_POST['logo'];
				$criteria['url'] = $_POST['url'];
				$criteria['description'] = $_POST['description'];
				$criteria['tel'] = $_POST['tel'];
				$criteria['email'] = $_POST['email'];
				$criteria['premium'] = $_POST['premium'];
				$criteria['gallery'] = $_POST['gallery'];
				
				$sl1->update_stockist($criteria);
				
				echo '<script>window.location = "./admin.php?page=stockist";</script>';
			}
			
			else {
				$map_url = 'http://maps.google.com/maps/api/staticmap?center='.$stockist[0]['lat'].','.$stockist[0]['lng'].'&zoom=15&size=300x200&markers=color:red|'.$stockist[0]['lat'].','.$stockist[0]['lng'].'&sensor=false';
				$map = '<img src="'.$map_url.'">';
				
				echo '<form id="add_stockist_form" method="post" style="display:none;">';
					echo '<p><label>Address <small>(Full address, including the zip code and country)</small></label></p>';
					echo '<p><input class="widefat" type="text" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"
					id="address2geocode" name="address2geocode"></p>';
					echo '<p class="submit" style="padding-bottom:0px; padding-top:0px;">';
					echo '<input id="geocode_address_btn" class="button-primary" type="submit" name="add" value="Geocode and preview this address">';
					echo '</p>';
				echo '</form>';
				
				echo '<form id="add_stockist_form2" method="post">';
					
					echo '<input type="hidden" id="lat" name="lat" value="'.$stockist[0]['lat'].'">';
					echo '<input type="hidden" id="lng" name="lng" value="'.$stockist[0]['lng'].'">';
					echo '<input type="hidden" id="address" name="address" value="'.$stockist[0]['address'].'">';
					
					echo '<table><tr><td id="map_display" style="padding-right:20px;">'.$map.'</td>';
					echo '<td valign="top"><b>Address:</b><br><span id="address_display">'.$stockist[0]['address'].'</span>
					<br><a href="#" id="edit_geocode_address">Edit address</a>
					</td></tr></table>';
					
					echo '<p><label>Is Premium Stockist? </label> ';
					echo '<input class="widefat" type="checkbox" name="premium" value="1" ' . (($stockist[0]['premium'])?"checked='checked'":'') . ' style="font-family: \'Courier New\', Courier, mono; font-size: 1.4em;" /></p>';
					
					echo '<p><label>Stockist name</label></p>';
					echo '<p><input class="widefat" type="text" name="name" value="'.$stockist[0]['name'].'" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
					
					echo '<p><label>Category</label> <a href="#" id="add_new_category_action">(add new)</a></p>';
					echo '<p><select class="widefat" name="category[]" multiple>';
					$sl1 = new Stockist_db();
					$categories = $sl1->return_categories();
					for($i=0; $i<count($categories);$i++) {
						if(in_array($categories[$i]['id'],$stockist[0]['category'])) echo '<option value="'.$categories[$i]['id'].'" selected>'.$categories[$i]['name'].'</option>';
						else echo '<option value="'.$categories[$i]['id'].'">'.$categories[$i]['name'].'</option>';
					}
					echo '</select></p>';
	
					echo '<label for="upload_image">';
					echo '<input id="upload_image" type="text" size="70" name="logo" value="'.$stockist[0]['logo'].'" />';
					echo '<input id="upload_image_button" class="button" type="button" value="Upload Image" />';
					echo '<br />Logo URL <small>(link to the stockist image)</small> | Enter a URL or upload an image';
					echo '</label>';
					
					echo '<p><label>URL <small>(should start with http://)</small></label></p>';
					echo '<p><input class="widefat" type="text" name="url" value="'.$stockist[0]['url'].'" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
	
					echo '<p><label>Description</label></p>';
					echo '<p><textarea class="widefat" type="text" name="description" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;">'.$stockist[0]['description'].'</textarea>';
					
					echo '<p><label>Telephone</label></p>';
					echo '<p><input type="text" class="widefat" name="tel" value="'.$stockist[0]['tel'].'" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
					
					echo '<p><label>Email</label></p>';
					echo '<p><input type="text" class="widefat" name="email" value="'.$stockist[0]['email'].'" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
					
					echo '<p><label>Gallery</label></p>';
					echo '<p><input type="button" value="Manage Images" class="button" id="gallery_upload_image_button"></p>';
					echo '<p><input type="hidden" id="imageurls" name="gallery" value=\''.$stockist[0]['gallery'].'\'></p>';
					
					echo '<p class="submit" style="padding-bottom:0px; padding-top:0px;">';
					echo '<input class="button-primary" type="submit" name="update" value="Update stockist information">';
					echo '</p>';
				echo '</form>';
			}
			
			?>
			
		</div></div>
		
		<?php
	}
	
	function stockist_add() {
		?>
		
		<div class="wrap">
		<div class="metabox-holder">
		
			<h2>Add Stockist</h2>
			<hr style="background:#ddd;color:#ddd;height:1px;border:none;">
			
			<?php
			
			if(isset($_POST['update'])) {
				
				$user_id = get_current_user_id();
				
				$criteria['user_id'] = $user_id;
				$criteria['category'] = $_POST['category'];
				$criteria['address'] = $_POST['address'];
				$criteria['lat'] = $_POST['lat'];
				$criteria['lng'] = $_POST['lng'];
				$criteria['name'] = $_POST['name'];
				$criteria['url'] = $_POST['url'];
				$criteria['logo'] = $_POST['logo'];
				$criteria['description'] = $_POST['description'];
				$criteria['tel'] = $_POST['tel'];
				$criteria['email'] = $_POST['email'];
				$criteria['premium'] = $_POST['premium'];
				$criteria['gallery'] = $_POST['gallery'];
				
				$sl1 = new Stockist_db();
				$sl1->add_stockist($criteria);
				
				echo '<script>window.location = "./admin.php?page=stockist";</script>';
			}
			
			else {
				echo '<form id="add_stockist_form" method="post">';
					echo '<p><label>Address <small>(Full address, including the zip code and country)</small></label></p>';
					echo '<p><input class="widefat" type="text" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"
					id="address2geocode" name="address2geocode"></p>';
					echo '<p class="submit" style="padding-bottom:0px; padding-top:0px;">';
					echo '<input id="geocode_address_btn" class="button-primary" type="submit" name="add" value="Geocode and preview this address">';
					echo '</p>';
				echo '</form>';
				
				echo '<form id="add_stockist_form2" method="post" style="display:none;">';
					
					echo '<table><tr><td id="map_display" style="padding-right:20px;"></td>';
					echo '<td valign="top"><b>Address:</b><br><span id="address_display"></span>
					<br><a href="#" id="edit_geocode_address">Edit address</a>
					</td></tr></table>';
					
					echo '<input type="hidden" id="lat" name="lat">';
					echo '<input type="hidden" id="lng" name="lng">';
					echo '<input type="hidden" id="address" name="address">';
					
					echo '<p><label>Is Premium Stockist? </label> ';
					echo '<input class="widefat" type="checkbox" name="premium" value="1" style="font-family: \'Courier New\', Courier, mono; font-size: 1.4em;" /></p>';
					
					echo '<p><label>Stockist name</label></p>';
					echo '<p><input class="widefat" type="text" name="name" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
					
					echo '<p><label>Category</label> <a href="#" id="add_new_category_action">(add new)</a></p>';
					echo '<p><select class="widefat" name="category[]" multiple>';
					$sl1 = new Stockist_db();
					$categories = $sl1->return_categories();
					for($i=0; $i<count($categories);$i++) {
						echo '<option value="'.$categories[$i]['id'].'">'.$categories[$i]['name'].'</option>';
					}
					echo '</select></p>';
					
					echo '<label for="upload_image">';
					echo '<input id="upload_image" type="text" size="70" name="logo" value="'.$stockist[0]['logo'].'" />';
					echo '<input id="upload_image_button" class="button" type="button" value="Upload Image" />';
					echo '<br />Logo URL <small>(link to the stockist image)</small> | Enter a URL or upload an image';
					echo '</label>';
					
					echo '<p><label>URL <small>(should start with http://)</small></label></p>';
					echo '<p><input class="widefat" type="text" name="url" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';

					echo '<p><label>Description</label></p>';
					echo '<p><textarea class="widefat" type="text" name="description" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></textarea>';
					
					echo '<p><label>Telephone</label></p>';
					echo '<p><input type="text" class="widefat" name="tel" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
					
					echo '<p><label>Email</label></p>';
					echo '<p><input type="text" class="widefat" name="email" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"></p>';
					
					echo '<p><label>Gallery</label></p>';
					echo '<p><input type="button" value="Manage Images" class="button" id="gallery_upload_image_button"></p>';
					echo '<p><input type="hidden" id="imageurls" name="gallery" value="'.$stockist[0]['gallery'].'"></p>';
					
					echo '<p class="submit" style="padding-bottom:0px; padding-top:0px;">';
					echo '<input class="button-primary" type="submit" name="update" value="Save stockist information">';
					echo '</p>';
				echo '</form>';
			}
			
			?>
			
		</div></div>
		<?php
	}
}

new Stockist_admin();

?>