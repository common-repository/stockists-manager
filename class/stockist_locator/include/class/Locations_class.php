<?php

class Locations_class {
	
	
	function displaySearch($address='', $categoryid='') {
		
		echo '<form method="GET">';
			
			$categories = $this->getCategories();
			
			echo '<input type="text" id="address" name="address" size="40" value="'.$address.'" style="font-size:18px;padding:2px; margin:2px;" />';
			echo '<select id="categoryid" name="categoryid" style="font-size:18px;padding:2px; margin:2px;">';
			echo '<option value="">Tous</option>';
			for($i=0; $i<count($categories); $i++) {
				if($categories[$i]['id']==$categoryid) echo '<option selected value="'.$categories[$i]['id'].'">'.$categories[$i]['name'].'</option>';
				else echo '<option value="'.$categories[$i]['id'].'">'.$categories[$i]['name'].'</option>';
			}
			echo '</select>';
			
			echo '<input type="submit" value="Search" style="font-size:18px;padding:2px; margin:2px;" />';
			
		echo '</form>';
	}
	
	function getCategories() {
		$tab_category_school = array("1"=>"Collège",
									"2"=>"Lycée",
									"3"=>"Centre de formation",
									"4"=>"Ecole d'ingénieurs",
									"5"=>"Ecole de commerce",
									"6"=>"Ecole de design",
									"7"=>"Ecole préparant au BTS/DUT",
									"8"=>"Université",
									);
		$i=0;
		foreach($tab_category_school as $ind => $row) {
			$categories[$i]['id'] = $ind;
			$categories[$i]['name'] = $row;
			$i++;
		}
		return $categories;
	}
	
	//6371 for km - 3959 for miles
	function returnSQLByCategory($criteria) {
		$lat = $criteria['lat'];
		$lng = $criteria['lng'];
		$categoryid = $criteria['categoryid'];
		$page_number = $criteria['page_number'];
		$nb_display = $criteria['nb_display'];
		
		//if($page_number=='') $page_number=1;
		//if($nb_display=='') $nb_display=10;
		$start = ($page_number*$nb_display)-$nb_display;
		
		$sql = "SELECT l.loginid, l.etablissementid, e.name, e.logo picture, l.address, l.lat, l.lng, 
		( 6371 * acos( cos( radians('".mysql_real_escape_string($lat)."') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('".mysql_real_escape_string($lng)."') ) + sin( radians('".mysql_real_escape_string($lat)."') ) * sin( radians( lat ) ) ) ) AS distance 
		FROM location l, etablissement e 
		WHERE l.etablissementid=e.id AND l.active=1"; //categoryid='$categoryid'
		
		if($categoryid!='') $sql .= " AND e.categoryid='".$categoryid."'";
		
		$sql .= " ORDER BY distance LIMIT $start , $nb_display";
		
		return $sql;
	}
	
	function getLocations($criteria) {
		$lat = $criteria['lat'];
		$lng = $criteria['lng'];
		$categoryid = $criteria['categoryid'];
		$nb_display = $criteria['nb_display'];
		$page_number = $criteria['page_number'];
		
		$m1 = new MySqlTable_stockist();
		$sql = $this->returnSQLByCategory($criteria);
		$result = $m1->customQuery($sql);
		
		if(count($result>0)) {
			$i=0;
			foreach($result as $row) {
				$locations[$i]['etablissementid'] = $row['etablissementid'];
				$locations[$i]['name'] = $row['name'];
				$locations[$i]['picture'] = $row['picture'];
				$locations[$i]['address'] = $row['address'];
				$locations[$i]['lat'] = $row['lat'];
				$locations[$i]['lng'] = $row['lng'];
				$locations[$i]['distance'] = $row['distance'];
				$locations[$i]['categoryid'] = $categoryid;
				$i++;
			}
		}
		
		return $locations;
	}
}

?>