<?php

require_once 'lib/RssBridge.php';


Bridge::setDir('bridges/');
Format::setDir('formats/');
Cache::setDir('caches/');

class coverageTest extends PHPUnit_Framework_TestCase {

	public function testCoverage() {

		$covered = true;

		foreach(Bridge::listBridges() as $bridgeName) {

			$testPath = 'tests/' . $bridgeName . 'Test.php';
			if(!file_exists($testPath)) {
				echo 'Bridge ' . $bridgeName . ' does not have a test !' . "\n";
				$covered = false;
			}

		}

		if($covered) {
			$this->success('Everything is covered !');
		} else {
			$this->fail('Some bridges are missing tests !');
		}
	}

}
?>
