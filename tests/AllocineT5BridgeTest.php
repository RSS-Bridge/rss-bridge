<?php
class AllocineT5BridgeTest extends BridgeTest{

    public $bridgeName = 'AllocineT5Bridge';
    
    public function testBridge() {

		$this->defaultTest($this->bridgeDatas,
							[
								BridgeTest::TEST_TITLE,
								BridgeTest::TEST_CONTENT,
								BridgeTest::TEST_URI
							], 5);

        
    }


}
