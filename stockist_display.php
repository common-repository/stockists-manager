<?php

class Stockist_display {
	
	function Stockist_display() {
		//add_filter('the_content', array(__CLASS__, 'display_linked_stockist'), 11);
	}
	
	function get_stockist_details_display($stockists) {
		$options = get_option(STOCKIST_SETTINGS_details);
		$width = $options['width'];
		$height = $options['height'];
		$streetview = $options['streetview'];
		$current_url = get_permalink();
		
		$display .= '<style type="text/css">#map img { max-width: none; }</style>';
		
		$streetview_thumbnail = 'http://cbk0.google.com/cbk?output=thumbnail&w=316&h=208&ll='.$stockists[0]['lat'].','.$stockists[0]['lng'];
		
		if($stockists[0]['logo']!='') $display .= '<img src="'.$stockists[0]['logo'].'" style="margin-bottom:10px; margin-top:10px;"><br>';
		
		$display .= '<b>'.$GLOBALS['stockist_lang']['stockist_name'].':</b> '.$stockists[0]['name'].' <small>(<a href="'.$current_url.'">'.$GLOBALS['stockist_lang']['view_all_stockists'].'</a>)</small><br>';
		$display .= '<b>'.$GLOBALS['stockist_lang']['address'].':</b> '.$stockists[0]['address'].'<br>';
		$display .= '<div id="map" style="overflow: hidden; width:'.$width.'; height:'.$height.'; max-width: none;"></div><br>';
		
		if($streetview=='on') $display .= '<div><img src="'.$streetview_thumbnail.'" style="overflow: hidden; width:'.$width.'; height:'.$height.'"></div>';
		
		if($stockists[0]['url']!='') $display .= '<b>'.$GLOBALS['stockist_lang']['url'].':</b> <a href="'.$stockists[0]['url'].'" target="_blank">'.$stockists[0]['url'].'</a><br>';
		if($stockists[0]['tel']!='') $display .= '<b>'.$GLOBALS['stockist_lang']['tel'].':</b> '.$stockists[0]['tel'].'<br>';
		if($stockists[0]['email']!='') $display .= '<b>'.$GLOBALS['stockist_lang']['email'].':</b> '.$stockists[0]['email'].'<br>';
		if($stockists[0]['description']!='') $display .= '<br><b>'.$GLOBALS['stockist_lang']['description'].'</b><br>'.$stockists[0]['description'].'<br>';
		
		if($stockists[0]['gallery']!=''){
			$display .= '<br><b>'.$GLOBALS['stockist_lang']['gallery'].':</b><br>';
			$gallery = (array) json_decode( $stockists[0]['gallery'] );
			foreach( $gallery['urls'] as $img_url )
				$display .= "<div><img src='{$img_url}' style='margin-bottom:10px; margin-top:10px;'></div>";
		}
		
		return $display;
	}
	
	function display_stockists_list($stockists,$criteria=array()) {
		global $woocommerce;

		$post = get_post( $_REQUEST['product'] );
		
		$no_info_links = $criteria['no_info_links']; //display info links or no
		$distance_display = $criteria['distance_display']; //display distance or no
		$widget_display = $criteria['widget_display'];
		
		$options = get_option(STOCKIST_SETTINGS);
		
		$current_url = get_permalink();

		for($i=0; $i<count($stockists); $i++) {
			
			$map_url = 'http://maps.google.com/maps/api/staticmap?center='.$stockists[$i]['lat'].','.$stockists[$i]['lng'].'&zoom=15&size=160x90&markers=color:red|'.$stockists[$i]['lat'].','.$stockists[$i]['lng'].'&sensor=false';
			
			if(count($stockists)>1) $content .= '<div style="padding-bottom:10px; border-bottom: 1px solid #e7e7e7; overflow:hidden;">';
			else $content .= '<div style="padding-bottom:10px; overflow:hidden;">';
			$content .= '<img src="'.$map_url.'" style="float:left; margin-right:25px; margin-bottom:5px;">';
			
			$content .= '<a href="'.get_permalink( $post->ID ).'?stockist_id='.$stockists[$i]['id'].'")><b>'.$stockists[$i]['name'].'</b></a>';
			
			if($distance_display) $content .= ' (<font color="red">'.number_format($stockists[$i]['distance'],1).' '.$options['distance'].'</font>)';
			$content .= '<br>';
			$content .= $stockists[$i]['address'].'';
			
			//more info links
			if($no_info_links!=1) {
				$content .= '<br><small><a href="'.get_permalink( $post->ID ).'?stockist_id='.$stockists[$i]['id'].'">'.$GLOBALS['stockist_lang']['more_information'].'</a></small>';
			}
			
			$content .= '</div>';
			$content .= '<br>';
		}

		return $content;
	}
	
	function display_stockists_list2($stockists,$criteria=array()) {
		global $post;

		$no_info_links = $criteria['no_info_links']; //display info links or no
		$distance_display = $criteria['distance_display']; //display distance or no
		$widget_display = $criteria['widget_display'];
		
		$options = get_option(STOCKIST_SETTINGS);
				
		$current_url = get_permalink();
		
		$content .= '<table style="width:100%; padding:0px; margin:0px; border:0px; margin-bottom:10px;">';
		
		$content .= '<tr>
		<th width="33%" style="border:0px; border-bottom: 1px solid #DDDDDD;">Name</th>
		<th width="43%" style="border:0px; border-bottom: 1px solid #DDDDDD;">Address</th>
		<th width="24%" style="border:0px; border-bottom: 1px solid #DDDDDD;">Category</th>
		</tr>';
		
		for($i=0; $i<count($stockists); $i++) {
			$id = $stockists[$i]['id'];
			$name = $stockists[$i]['name'];
			$logo = $stockists[$i]['logo'];
			$address = $stockists[$i]['address'];
			$url = $stockists[$i]['url'];
			$lat = $stockists[$i]['lat'];
			$lng = $stockists[$i]['lng'];
			$tel = $stockists[$i]['tel'];
			$distance = $stockists[$i]['distance'];
			$category_name = $stockists[$i]['category_name'];
			$marker_icon = $stockists[$i]['marker_icon'];
			
			$marker_text = self::getMarkerInfowindowDisplay(array('id'=>$id, 'name'=>$name, 'logo'=>$logo, 'address'=>$address, 'url'=>$url, 'streetview'=>$options['streetview'], 'directions'=>$options['directions'], 'more_details'=>1));
			$content .= '<div style="display:none;" id="infowindow_'.$id.'">'.$marker_text.'</div>';
			$content .= '<div style="display:none;" id="marker_icon_'.$id.'">'.$marker_icon.'</div>';
			
			$content .= '<tr class="displayStockistMap" id="'.$id.'" lat="'.$lat.'" lng="'.$lng.'"
			style="border:0px; cursor:pointer;" onMouseOver="this.style.backgroundColor=\'#eee\'"; onMouseOut="this.style.backgroundColor=\'#fff\'">';
			
				$content .= '<td width="33%" style="padding-right:15px; vertical-align:top; border:0px;">';
					
					$content .= '<a href="'.get_permalink( $post->ID ).'?stockist_id='.$id.'">'.$name.'</a>';
					
				$content .= '</td>';
				
				$content .= '<td style="padding-right:15px; vertical-align:top; width:43%; border:0px;">'.$address.'</td>';
				
				$content .= '<td style="padding-right:15px; vertical-align:top; width:24%; border:0px;">'.$category_name.'</td>';
				
			$content .= '</tr>';
		}
		
		$content .= '<tr style="border:0px; margin:0px; padding:0px;">
		<td colspan=3 style="border:0px; border-bottom: 1px solid #DDDDDD; margin:0px; padding:0px;"></td>
		</tr>';
		
		$content .= '</table>';
		
		return $content;
	}
	
	function getMarkerInfowindowDisplay($criteria=array()) {
		$id = $criteria['id'];
		$name = $criteria['name'];
		$address = $criteria['address'];
		$url = $criteria['url'];
		$logo = $criteria['logo'];
		$streetview = $criteria['streetview'];
		$directions = $criteria['directions'];
		$more_details = $criteria['more_details'];
		
		$d .= '<div style="font-size: 12px !important; overflow:hidden !important; padding: 0px !important; margin: 0px !important; color: black !important; font-family: arial,sans-serif !important; line-height: normal !important; width:360px;">';
			
			if($logo!='') {
				if($url!='') $d .= '<a href="'.$url.'" target="_blank">';
				$d .= '<img src="'.$logo.'" align="left" style="padding-right:10px;" border=0>';
				if($url!='') $d .= '</a>';
			}
			
			if($url!='') $d .= '<a href="'.$url.'" target="_blank">';
			$d .= '<b>'.$name.'</b>';
			if($url!='') $d .= '</a>';
			
			$d .= '<br>'.$address;
			
			if($more_details==1 || $streetview=='on') {
				$detail_page = get_permalink();
				$d .= '<div style="margin-top:5px;">';
					if($more_details==1) $d .= '<a href="'.get_permalink( $_REQUEST['product'] ).'?stockist_id='.$id.'">'.$GLOBALS['stockist_lang']['more_details'].'</a>';
					if($streetview=='on') $d .= ' - <a href="#" id="displayStreetView">'.$GLOBALS['stockist_lang']['streetview'].'</a>';
				$d .= '</div>';
			}
			
			if($directions=='on') {
				$d .= '<div style="margin-top:5px;">';
				$address = str_replace('<br />', ' ', $address);
				$d .= $GLOBALS['stockist_lang']['get_directions'].': <a href="http://maps.google.com/maps?f=d&z=13&daddr='.urlencode($address).'" target="_blank">'.$GLOBALS['stockist_lang']['to_here'].'</a> - <a href="http://maps.google.com/maps?f=d&z=13&saddr='.urlencode($address).'" target="_blank">'.$GLOBALS['stockist_lang']['from_here'].'</a>';
				$d .= '</div>';
			}
			
		$d .= '</div>';
		
		return $d;
	}
}

new Stockist_display();

?>