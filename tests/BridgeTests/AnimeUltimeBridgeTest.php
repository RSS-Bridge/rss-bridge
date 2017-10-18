<?php
class AnimeUltimeBridgeTest extends BridgeTest{

    public $bridgeName = 'AnimeUltimeBridge';

    function loadParameters() {
        return array(

            array("type" => ""),
            array("type" => "A"),
            array("type" => "D"),
            array("type" => "T"),
            

        );

    }

    public function testBridgeTypeAll() {

		$this->defaultTest($this->bridgeDatas,
							[
								BridgeTest::TEST_TITLE,
								BridgeTest::TEST_CONTENT,
								BridgeTest::TEST_URI,
								BridgeTest::TEST_TIMESTAMP
							], 5);

        
    }

    public function testBridgeTypeAnime() {

        $this->testBridgeTypeAll();
        
    }

    public function testBridgeTypeDrama() {

        $this->testBridgeTypeAll();
        
    }

    public function testBridgeTypeTokusatsu() {

        $this->testBridgeTypeAll();
        
    }


}
