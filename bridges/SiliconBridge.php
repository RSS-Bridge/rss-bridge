<?php
require_once('WordPressBridge.php');

class SiliconBridge extends WordPressBridge {

	const MAINTAINER = "ORelio";
	const NAME = 'Silicon Bridge';
	const URI = 'http://www.silicon.fr/';
	const DESCRIPTION = "Returns the newest articles.";
	const PARAMETERS = array();

	public function getCacheDuration() {
		return 1800; // 30 minutes
	}
}
