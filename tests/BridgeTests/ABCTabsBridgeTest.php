<?php
class ABCTabsBridgeTest extends BridgeTest {

    public $bridgeName = 'ABCTabsBridge';
    
    public function testBridge() {

		$this->defaultTest($this->bridgeDatas,
							[
								BridgeTest::TEST_NAME,
								BridgeTest::TEST_ID,
								BridgeTest::TEST_TITLE,
								BridgeTest::TEST_CONTENT,
								BridgeTest::TEST_URI
							], 5);

    }

}
