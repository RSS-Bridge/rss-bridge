<?php
require_once('WordPressBridge.php');

class LeJournalDuGeekBridge extends WordPressBridge{

	const MAINTAINER = "polopollo";
	const NAME = "journaldugeek.com (FR)";
	const URI = "http://www.journaldugeek.com/";
	const DESCRIPTION = "Returns the newest posts from LeJournalDuGeek (full text).";
	const PARAMETERS = array();

	public function getCacheDuration(){
		return 1800; // 30min
	}
}
