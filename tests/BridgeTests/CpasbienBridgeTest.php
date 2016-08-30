<?php
class CpasbienBridgeTest extends BridgeTest {
	public $bridgeName = 'CpasbienBridge';
	
	function loadParameters(){
	return array(
		array('q' => 'france')
	);
	}
	
	public function testSearchFrance() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_NAME, BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_ID, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}
}
?>