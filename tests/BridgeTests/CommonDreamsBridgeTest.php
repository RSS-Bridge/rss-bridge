<?php
class CommonDreamsBridgeTest extends BridgeTest {
	public $bridgeName = 'CommonDreamsBridge';
	
	public function testBridge() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}
}
?>