<?php
class AllocineTueursEnSerieBridgeTest extends BridgeTest{

    public $bridgeName = 'AllocineTueursEnSerieBridge';
    
    public function testBridge() {

		$this->defaultTest($this->bridgeDatas,
							[
								BridgeTest::TEST_TITLE,
								BridgeTest::TEST_CONTENT,
								BridgeTest::TEST_URI
							], 5);
        
    }


}
