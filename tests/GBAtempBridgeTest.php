<?php
class GBAtempBridgeTest extends BridgeTest {
	public $bridgeName = 'GBAtempBridge';
	
	function loadParameters(){
		return array(
			array('type' => 'N'), // News
			array('type' => 'R'), // Reviews
			array('type' => 'T'), // Tutorials
			array('type' => 'F') // Forum
		);
	}
	
	// Test for type=N
	public function testNews() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}

	// Test for type=R
	public function testReviews() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}
	
	// Test for type=T
	public function testTutorials() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}
	
	// Test for type=F
	public function testForum() {
		$this->defaultTest($this->bridgeDatas
			, [BridgeTest::TEST_TITLE, BridgeTest::TEST_CONTENT, BridgeTest::TEST_URI, BridgeTest::TEST_TIMESTAMP]
			, 1);
	}
}
?>