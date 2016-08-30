<?php
class DauphineLibereBridgeTest extends BridgeTest {
	public $bridgeName = 'DauphineLibereBridge';
	
	function loadParameters(){
		return array(
			array(),
			array('u' => 'sport')
		);
	}
	
	// Test without parameters
	public function testCategoryNone() {
	$this->defaultTest($this->bridgeDatas
		, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
		, 1);
	}
	
	// Test for u=sport
	public function testCategorySport() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}
}
?>