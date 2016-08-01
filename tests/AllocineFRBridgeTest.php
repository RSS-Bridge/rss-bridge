<?php
class AllocineFRBridgeTest extends BridgeTest{

    public $bridgeName = 'AllocineFRBridge';
    
    public function testBridge() {

		$this->defaultTest($this->bridgeDatas,
							[
								BridgeTest::TEST_TITLE,
								BridgeTest::TEST_CONTENT,
								BridgeTest::TEST_URI
							], 5);
        
    }


}
