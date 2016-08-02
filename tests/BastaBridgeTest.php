<?php
class BastaBridgeTest extends BridgeTest {
	public $bridgeName = 'BastaBridge';
	
	public function testBridge() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}
}
?>