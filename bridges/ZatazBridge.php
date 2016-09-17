<?php
require_once('WordPressBridge.php');

class ZatazBridge extends WordPressBridge{

	const MAINTAINER = "aledeg";
	const NAME = 'Zataz Magazine';
	const URI = 'http://www.zataz.com';
	const DESCRIPTION = "ZATAZ Magazine - S'informer, c'est déjà se sécuriser";
	const PARAMETERS = array();

	public function getCacheDuration() {
		return 7200; // 2h
	}

}
