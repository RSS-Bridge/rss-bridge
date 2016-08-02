<?php
class CADBridgeTest extends BridgeTest {
	public $bridgeName = 'CADBridge';
	
	public function testBridge() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}
}
?>