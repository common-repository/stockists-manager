<?php

class Stockist_framework {
	
	//set default values
	function set_default_values($criteria=array()) {
		$key = $criteria['key'];
		$default_values = $criteria['default_values'];
		
		$options = get_option($key);
		foreach($default_values as $ind => $value) {
			if($options[$ind]=='') {
				$options[$ind]=$value;
			}
		}
		update_option($key, $options);
	}
	
	//display widget control
	function display_widget_control($criteria=array()) {
		$key = $criteria['key'];
		$fields = $criteria['fields'];
		
		$options = get_option($key);
		if(!is_array($options)) {
			$options = array();
		}
		
		$widget_data = $_POST[$key];
		if($widget_data['submit']) {
			for($i=0; $i<count($fields); $i++) {
				$options[$fields[$i]['name']] = $widget_data[$fields[$i]['name']];
			}
			update_option($key, $options);
		}
		
		for($i=0; $i<count($fields); $i++) {
			if($fields[$i]['type']=='select') {
				echo '<p>';
					echo '<label>'.$fields[$i]['title'].'</label>';
					echo '<select class="widefat" name="'.$key.'['.$fields[$i]['name'].']">';
					echo '<option value=""></option>';
					foreach($fields[$i]['select_values'] as $ind=>$value) {
						if($ind==$options[$fields[$i]['name']]) echo '<option value="'.$ind.'" selected>'.$value.'</option>';
						else echo '<option value="'.$ind.'">'.$value.'</option>';
					}
					echo '</select>';
				echo '</p>';
			}
			else {
				echo '<p>';
					echo '<label>'.$fields[$i]['title'].'</label>';
					echo '<input class="widefat" type="text" name="'.$key.'['.$fields[$i]['name'].']" value="'.$options[$fields[$i]['name']].'">';
				echo '</p>';
			}
		}
		
		echo '<input type="hidden" name="'.$key.'[submit]" value="1">';
	}
	
	//display admin control
	function display_admin_control($criteria=array()) {
		$key = $criteria['key'];
		$box_title = $criteria['box_title'];
		$fields = $criteria['fields'];
		
		$options = get_option($key);
		if(!is_array($options)) {
			$options = array();
		}
		
		$widget_data = $_POST[$key];
		if($widget_data['submit']) {
			for($i=0; $i<count($fields); $i++) {
				$options[$fields[$i]['name']] = $widget_data[$fields[$i]['name']];
			}
			update_option($key, $options);
		}
		
		echo '<div class="meta-box-sortables">';
			echo '<div class="postbox" style="width:100%;">';
			echo '<div class="handlediv" title="Click to toggle"><br /></div>';
			echo '<h3 class="hndle"><span>'.$box_title.'</span></h3>';
		
				echo '<div class="inside" style="width:95%; padding:10px;">';
				echo '<form method="post">';
				
					for($i=0; $i<count($fields); $i++) {
						if($fields[$i]['type']=='select') {
							echo '<p><label>'.$fields[$i]['title'].'</label></p>';
							echo '<p><select class="widefat" name="'.$key.'['.$fields[$i]['name'].']">';
							foreach($fields[$i]['select_values'] as $ind=>$value) {
								if($ind==$options[$fields[$i]['name']]) echo '<option value="'.$ind.'" selected>'.$value.'</option>';
								else echo '<option value="'.$ind.'">'.$value.'</option>';
							}
							echo '</select></p>';
						}
						elseif($fields[$i]['type']=='checkbox') {
							echo '<p><label>';
							if($options[$fields[$i]['name']]=='on') $checked='checked';
							else $checked='';
							echo '<input type="checkbox" name="'.$key.'['.$fields[$i]['name'].']" '.$checked.' style="margin-bottom:4px;">';
							echo ' '.$fields[$i]['title'];
							echo '</label></p>';
						}
						elseif($fields[$i]['type']=='textarea') {
							echo '<p><label>'.$fields[$i]['title'].'</label></p>';
							echo '<p><textarea class="widefat" type="text" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"
							name="'.$key.'['.$fields[$i]['name'].']">'.$options[$fields[$i]['name']].'</textarea></p>';
						}
						else {
							echo '<p><label>'.$fields[$i]['title'].'</label></p>';
							echo '<p><input class="widefat" type="text" style="width:100%; font-family: \'Courier New\', Courier, mono; font-size: 1.4em;"
							name="'.$key.'['.$fields[$i]['name'].']" value="'.$options[$fields[$i]['name']].'"></p>';
						}
					}
					
					echo '<p class="submit" style="padding-bottom:0px; padding-top:0px;">';
					echo '<input type="submit" name="'.$key.'[submit]" value="Update options &raquo;">';
					echo '</p>';
				
				echo '</form>';
				echo '</div>';
		
			echo '</div>';
		echo '</div>';
	}
}

?>