<?php
class Cookie {
	private $cookieLocation;
	
	public function __construct($cookieName, $string = "", $lifeTime) {
		$this->cookieLocation = $cookieName;
		$this->save($string, $lifeTime);
	}
	
    public function save($string, $lifeTime) {
		setcookie($this->cookieLocation, $string, $lifeTime);
	}

	public function load() {
		
		if (isset($_COOKIE[$this->cookieLocation])) {
			$ret = $_COOKIE[$this->cookieLocation];
		}
		else {
			$ret = "";
		}
		
		return $ret;
	}
	
	public function destroy() {
		setcookie($this->cookieLocation, NULL, 1);
	}
}
?>