<?php

require_once __DIR__ . '/TwitterBridgeExtended.php';

class TwitterBridgeTweaked extends TwitterBridgeExtended{


	public function getUsername(){
		return $this->items[0]->username;
	}
}
