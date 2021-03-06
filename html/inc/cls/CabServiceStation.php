<?php

class CabServiceStation {

	const MAX_RATING = 5;
	
	public $id;
	protected $data, $rating, $numRatings;

	function __construct($row) {
		$this->data = $row;
		$this->id = $row['id'];
		$this->rating = -1;
		$this->numRatings = 0;
	}

	function __toString() {
		return 'css-'.$this->id;
	}

	function name() {
		return $this->data['name'];
	}

	function phoneNumber() {
		return $this->data['phone'];
	}

	function phoneNumberDigits() {
		return preg_replace('/D/', '', $this->data['phone']);
	}

	function website() {
		return $this->data['website'];
	}

	function city() {
		return $this->data['city'];
	}

	function state() {
		return $this->data['state'];
	}

	function latLng() {
		return new LatLng($this->data['latitude'], $this->data['longitude']);
	}
	
	function latitude() {
		return $this->data['latitude'];
	}

	function longitude() {
		return $this->data['longitude'];
	}

	/** How far will they travel in miles */
	function range() {
		return $this->data['range'];
	}

	function addRating($val) {
		// todo: protect this from being exploited/inflated
		$q = sprintf("INSERT INTO gc_station_rating VALUES(%d, %d, NOW());",
			$this->id, $val);
		$r = GCConfig::$db->query($q);
	}

	function rating() {
		if($this->rating < 0) {
			$q = sprintf("SELECT AVG(rating), COUNT(*) FROM gc_station_rating WHERE css_id = %d AND time >= DATE_SUB(NOW(), INTERVAL 1 YEAR);",
				$this->id);
			$r = GCConfig::$db->query($q);
			if($row = $r->fetch_row()) {
				$this->rating = round($row[0]);
				$this->numRatings = $row[1];
			}
			else {
				$this->rating = 3;
				$this->numRatings = 0;
			}
		}
		return $this->rating;
	}

	function numRatings() {
		if($this->rating < 0) {
			$this->rating();
		}
		return $this->numRatings;
	}

	/** Return the estimated time for this station's dispatcher to respond, in seconds */
	function estimatedDispatchResponseTime() {
		return rand(1, 180);
	}

	function pendingOrders() {
		$q = sprintf("SELECT * FROM gc_service_order WHERE css_id = %d AND `status` = 'wait';",
			$this->id);
		$r = GCConfig::$db->query($q);
		$list = array();
		while($row = $r->fetch_assoc()) {
			array_push($list, new CabServiceOrder($row));
		}
		return $list;
	}

	/** Return a list of all stations who have active contracts */
	public static function activeList() {
		$q = "SELECT * FROM gc_service_station ORDER BY name ASC;";
		$r = GCConfig::$db->query($q);
		$list = array();
		while($row = $r->fetch_assoc()) {
			array_push($list, new CabServiceStation($row));
		}
		return $list;
	}

	public static function create($name, $phone, $website, $city, $state, $lat, $long, $range) {
		$db = GCConfig::$db;
		$q = sprintf("INSERT INTO gc_service_station VALUES(NULL, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d);",
			$db->real_escape_string($name), 
			$db->real_escape_string($phone),
			$db->real_escape_string($website),
			$db->real_escape_string($city),
			$db->real_escape_string($state),
			$db->real_escape_string($lat),
			$db->real_escape_string($long),
			$range);
		$r = $db->query($q) or die($db->error);
		$id = $db->insert_id;
		return self::load($id);
	}

	public static function load($id) {
		$q = sprintf("SELECT * FROM gc_service_station WHERE id = %d;",
			$id);
		$r = GCConfig::$db->query($q);
		if($row = $r->fetch_assoc()) {
			return new CabServiceStation($row);
		}
		return null;
	}

	public static function loadCSS($code) {
		$id = intval(str_replace('css-', '', $code));
		return self::load($id);
	}

}

?>
