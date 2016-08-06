<?php

require_once('lib/Bridge.php');

/*
	This class will check all bridges for proper implementation
*/
class BridgeImplementationTest extends PHPUnit_Framework_TestCase {

	// Returns an array of all bridge files (file names without file type)
	private function ListBridges(){
		$listBridge = array();
		$dirFiles = scandir('bridges/');

		if( $dirFiles !== false ){

			foreach( $dirFiles as $fileName ) {
				if( preg_match('@([^.]+)\.php$@U', $fileName, $out) ){
						$listBridge[] = $out[1];
				}
			}
		}
		return $listBridge;
	}

	// Checks if the bridge implements additional public functions (should not be!)
	private function CheckBridgePublicFunctions($bridgeName){
		$parent_methods = array();

		if(in_array('BridgeInterface', class_parents($bridgeName)))
			$parent_methods = array_merge($parent_methods, get_class_methods('BridgeInterface'));

		if(in_array('BridgeAbstract', class_parents($bridgeName)))
			$parent_methods = array_merge($parent_methods, get_class_methods('BridgeAbstract'));

		if(in_array('HttpCachingBridgeAbstract', class_parents($bridgeName)))
			$parent_methods = array_merge($parent_methods, get_class_methods('HttpCachingBridgeAbstract'));

		if(in_array('RssExpander', class_parents($bridgeName)))
			$parent_methods = array_merge($parent_methods, get_class_methods('RssExpander'));

		// Receive all non abstract methods
		$methods = array_diff(get_class_methods($bridgeName), $parent_methods);

		$method_names = '';
		foreach($methods as $method){
			if($method_names === '')
				$method_names .= $method;
			else
				$method_names .= ', ' . $method;
		}

		$parent_names = '';
		foreach(class_parents($bridgeName) as $parent){
			if($parent_names === '')
				$parent_names .= $parent;
			else
				$parent_names .= ', ' . $parent;
		}

		// There should generally be no additional (public) function
		$this->assertEmpty($methods, $bridgeName . ' extends (' . $parent_names . ') and defines additional public functions : ' . $method_names . '!');
	}

	public function testBridgeImplementation($bridgeName){
		require_once('bridges/' . $bridgeName . '.php');

		$this->CheckBridgePublicFunctions($bridgeName);
	}

	public function count()
	{
		return count($this->ListBridges());
	}

	public function run(PHPUnit_Framework_TestResult $result = NULL)
	{
		if ($result === NULL) {
			$result = new PHPUnit_Framework_TestResult;
		}

		foreach ($this->ListBridges() as $bridge) {
			$result->startTest($this);
			PHP_Timer::start();
			$stopTime = NULL;

			//list($expected, $actual) = explode(';', $bridge);

			try {
				$this->testBridgeImplementation($bridge);
			}

			catch (PHPUnit_Framework_AssertionFailedError $e) {
				$stopTime = PHP_Timer::stop();
				$result->addFailure($this, $e, $stopTime);
			}

			catch (Exception $e) {
				$stopTime = PHP_Timer::stop();
				$result->addError($this, $e, $stopTime);
			}

			if ($stopTime === NULL) {
				$stopTime = PHP_Timer::stop();
			}

			$result->endTest($this, $stopTime);
		}

		return $result;
	}
}
