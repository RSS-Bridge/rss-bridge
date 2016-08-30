<?php
class MangareaderBridgeTest extends BridgeTest {
	public $bridgeName = 'MangareaderBridge';
	
	// Returns an array of parameters, where each element defines a set of parameters for the given bridge
	function loadParameters(){
		return array(
			array(), // type=latest
			array('category' => 'all'), // type=popular
			array('path' => 'bleach', 'limit' => '10') // type=path
		);
	}
	
	// Test for type=latest
	public function testLatest() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI]
			, 1);
	}
	
	// Test for type=popular
	public function testPopular() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI]
			, 1);
	}
	
	// Test for type=path
	public function testPath() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 10);
	}
}
?>