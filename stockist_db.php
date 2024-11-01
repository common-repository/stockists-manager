<?php

class Stockist_db {
	
	var $wpdb;
	var $table_name;
	var $table_name_category;
	
	function Stockist_db() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = $wpdb->prefix . "stockist";
		$this->table_name_category = $wpdb->prefix . "stockist_category";
		$this->table_name_relation = $wpdb->prefix . "stockist_relation";
	}
	
	function setup_tables() {
		self::create_tables();
	}
	
	function create_tables() {
		$sql = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
		`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`user_id` BIGINT NOT NULL ,
		`name` VARCHAR( 160 ) NOT NULL ,
		`logo` VARCHAR( 160 ) NOT NULL ,
		`address` VARCHAR( 160 ) NOT NULL ,
		`lat` VARCHAR( 20 ) NOT NULL ,
		`lng` VARCHAR( 20 ) NOT NULL ,
		`url` VARCHAR( 160 ) NOT NULL ,
		`description` TEXT NOT NULL ,
		`tel` VARCHAR( 30 ) NOT NULL ,
		`email` VARCHAR( 60 ) NOT NULL ,
		`premium` INT(1) NOT NULL ,
		`gallery` TEXT NOT NULL ,
		`created` DATETIME NOT NULL
		) ENGINE = MYISAM;";
		$this->wpdb->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS " . $this->table_name_category . " (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`name` VARCHAR( 120 ) NOT NULL,
		`marker_icon` VARCHAR( 200 ) NOT NULL
		) ENGINE = MYISAM ;";
		
		$this->wpdb->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS " . $this->table_name_relation . " (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`stockist` BIGINT NOT NULL, 
		`category` BIGINT NOT NULL
		) ENGINE = MYISAM ;";
		
		$this->wpdb->query($sql);
	}

	function get_locations($criteria) {
		$lat = $criteria['lat'];
		$lng = $criteria['lng'];
		$page_number = $criteria['page_number'];
		$nb_display = $criteria['nb_display'];
		$distance_unit = $criteria['distance_unit'];
		$category_id = $criteria['category_id'];
		$radius_id = $criteria['radius_id'];
		
		$start = ($page_number*$nb_display)-$nb_display;
		
		if($distance_unit=='miles') $distance_unit='3959'; //miles
		else $distance_unit='6371'; //km
		
		$sql = "SELECT s.*, c.marker_icon, c.name category_name,
		( $distance_unit * acos( cos( radians('".$lat."') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('".$lng."') ) + sin( radians('".$lat."') ) * sin( radians( lat ) ) ) ) AS distance 
		FROM ".$this->table_name." s
		LEFT JOIN ".$this->table_name_relation." r
		ON s.id=r.stockist
		LEFT JOIN ".$this->table_name_category." c
		ON r.category=c.id
		WHERE 1 ";
		
		if($category_id!='') $sql .= " AND c.id='$category_id'";
		
		if($radius_id!='') $sql .= " HAVING distance<='".$radius_id."'";
		
		if($lat!=''&&$lng!='') $sql .= " ORDER BY premium DESC, distance";
		else $sql .= " ORDER BY premium DESC, id DESC";
		
		if($nb_display!='') $sql .= " LIMIT $start, $nb_display";
		
		$locations = $this->wpdb->get_results($sql, 'ARRAY_A');
		
		return $locations;
	}
	
	function return_nb_stockists($criteria=array()) {
		$category_id = $criteria['category_id'];
		
		$sql = "SELECT count(*) as nb 
		FROM $this->table_name WHERE 1";
		
		if($category_id!='')
			$sql = " SELECT count(s.*) as nb 
			FROM $this->table_name s, 
			".$this->table_name_relation." r, 
			".$this->table_name_category." c 
			WHERE s.id=r.stockist 
			AND r.category=c.id 
			AND c.id='$category_id'";
		
		$results = $this->wpdb->get_results($sql, 'ARRAY_A');
		return $results[0];
	}
	
	function return_stockists($criteria=array()) {
		$id = $criteria['id'];
		$category_id = $criteria['category_id'];
		
		if($category_id>0){
			$sql = "SELECT s.*, c.id AS category_id, c.marker_icon 
					FROM $this->table_name s 
					".$this->table_name_relation." r, 
					".$this->table_name_category." c, 
					WHERE s.id=r.stockist 
					AND r.category=c.id 
					AND c.id='$category_id'";
			
			if($id>0) $sql .= " AND s.id='$id'";
			$sql .= ' ORDER BY s.created DESC';
			
			$results = $this->wpdb->get_results($sql, 'ARRAY_A');
			
		}else{
			
			$sql = "SELECT * FROM $this->table_name WHERE 1 ";
			if($id>0) $sql .= " AND id='$id'";
			$sql .= ' ORDER BY created DESC';
			
			$results = $this->wpdb->get_results($sql, 'ARRAY_A');
			
			if( count( $results ) )
				foreach( $results as &$result ){
					$sql = "SELECT c.id AS category_id, c.marker_icon 
							FROM ".$this->table_name_relation." r 
							LEFT JOIN ".$this->table_name_category." c 
							ON r.category=c.id 
							WHERE r.stockist='{$result['id']}'";
					$results2 = $this->wpdb->get_results($sql, 'ARRAY_A');
					if( count( $results2 ) )
						foreach( $results2 as $result2 )
							$result['category'][] = $result2['category_id'];
				}
			unset( $result );
		}
		
		return $results;
	}
	
	function return_categories($criteria=array()) {
		$id = $criteria['id'];
		$sql = "SELECT * FROM $this->table_name_category WHERE 1";
		if($id>0) $sql .= " AND id='$id'";
		$sql .= ' ORDER BY name';
		
		$results = $this->wpdb->get_results($sql, 'ARRAY_A');
		return $results;
	}
	
	function return_nb_stockists_by_category() {
		$sql = "SELECT c.id, count(*) nb 
		FROM ".$this->table_name." s, ".$this->table_name_relation." r, ".$this->table_name_category." c 
		WHERE s.id=r.stockist AND r.category=c.id GROUP BY c.id";
		$results = $this->wpdb->get_results($sql, 'ARRAY_A');
		for($i=0; $i<count($results); $i++) {
			$stockistsCat[$results[$i]['id']] = $results[$i]['nb'];
		}
		return $stockistsCat;
	}
	
	function delete_stockist($id) {
		$user_id = get_current_user_id();
		$sql = "SELECT * FROM $this->table_name WHERE id='$id' AND user_id='$user_id'";
		$results = $this->wpdb->get_results($sql, 'ARRAY_A');
		if(count($results)>0) {
			$sql = "DELETE FROM $this->table_name WHERE id='%d'";
			$this->wpdb->query($this->wpdb->prepare($sql, $id));
			$sql = "DELETE FROM $this->table_name_relation WHERE stockist='%d'";
			$this->wpdb->query($this->wpdb->prepare($sql, $id));
			return 'The stockist has been deleted.';
		}
		else {
			return 'Only the author of this stockist, can delete it.';
		}
	}
	
	function delete_category($id) {
		$sql = "SELECT * FROM $this->table_name_relation WHERE category='$id'";
		$results = $this->wpdb->get_results($sql, 'ARRAY_A');
		if(count($results)>0) {
			return "You cannot delete this category because it's containing ".count($results)." stockist(s). Please delete the stockists first then try again.";
		}
		else {
			$sql = "DELETE FROM $this->table_name_category WHERE id='%d'";
			$this->wpdb->query($this->wpdb->prepare($sql, $id));
			return 'The category has been deleted.';
		}
	}
	
	function update_stockist($criteria) {
		$sql = "UPDATE $this->table_name SET 
		name='".$criteria['name']."', logo='".$criteria['logo']."', url='".$criteria['url']."', 
		address='".$criteria['address']."', lat='".$criteria['lat']."', lng='".$criteria['lng']."', 
		description='".$criteria['description']."', tel='".$criteria['tel']."', email='".$criteria['email']."', 
		premium='".$criteria['premium']."', gallery='".$criteria['gallery']."' 
		WHERE id='".$criteria['id']."'";
		$this->wpdb->query($sql);
		
		//update category relations
		$sql = "DELETE FROM $this->table_name_relation WHERE stockist='%d'";
		$this->wpdb->query($this->wpdb->prepare($sql, $criteria['id']));
		
		if( count( $criteria['category'] ) ){
			foreach( $criteria['category'] as $category ){
				$sql = "INSERT INTO $this->table_name_relation ( `stockist`, `category` ) VALUES('%d', '%d')";
				$this->wpdb->query($this->wpdb->prepare($sql, $criteria['id'], $category));
			}
		}
	}
	
	function update_category($criteria) {
		$sql = "UPDATE $this->table_name_category SET name='".$criteria['name']."', marker_icon='".$criteria['marker_icon']."' 
		WHERE id='".$criteria['id']."'";
		$this->wpdb->query($sql);
	}
	
	function add_stockist($criteria) {
		$sql = "INSERT INTO $this->table_name 
		(user_id, name, logo, address, lat, lng, url, description, tel, email, premium, gallery, created) 
		VALUES ('".$criteria['user_id']."', '".$criteria['name']."', '".$criteria['logo']."', '".$criteria['address']."', '".$criteria['lat']."', '".$criteria['lng']."', 
		'".$criteria['url']."', '".$criteria['description']."', '".$criteria['tel']."', '".$criteria['email']."', '".$criteria['premium']."', '".$criteria['gallery']."', '".date('Y-m-d H:i:s')."')";
		$this->wpdb->query($sql);
		$last_id = $this->wpdb->insert_id;
		
		if( count( $criteria['category'] ) ){
			foreach( $criteria['category'] as $category ){
				$sql = "INSERT INTO $this->table_name_relation ( `stockist`, `category` ) VALUES('%d', '%d')";
				$this->wpdb->query($this->wpdb->prepare($sql, $last_id, $category));
			}
		}
	}
	
	function add_category($criteria=array()) {
		$name = $criteria['name'];
		$marker_icon = $criteria['marker_icon'];
		
		$sql = "INSERT INTO $this->table_name_category (name, marker_icon) VALUES ('".$name."', '".$marker_icon."')";
		$this->wpdb->query($sql);
	}
	
	function category_exist( $name = '' ) {
		return $this->wpdb->get_var( "SELECT count(*) FROM $this->table_name_category WHERE `name`='".$name."'" );
	}
}

?>