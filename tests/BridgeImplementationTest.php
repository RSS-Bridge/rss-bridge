<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\AssertionFailedError;

require_once(__DIR__ . '/../lib/RssBridge.php');

Bridge::setDir(__DIR__ . '/../bridges/');

/**
 * This class checks bridges for implementation details:
 *
 * - A bridge must not implement public functions other than the ones specified
 *   by the bridge interfaces. Custom functions must be defined in private or
 *   protected scope.
 * - getName() must return a valid string (non-empty)
 * - getURI() must return a valid URI
 * - A bridge must define constants for NAME, URI, DESCRIPTION and MAINTAINER,
 *   CACHE_TIMEOUT and PARAMETERS are optional
 */
final class BridgeImplementationTest extends TestCase {

	private function CheckBridgePublicFunctions($bridgeName){

		$parent_methods = array();

		if(in_array('BridgeInterface', class_parents($bridgeName))) {
			$parent_methods = array_merge($parent_methods, get_class_methods('BridgeInterface'));
		}

		if(in_array('BridgeAbstract', class_parents($bridgeName))) {
			$parent_methods = array_merge($parent_methods, get_class_methods('BridgeAbstract'));
		}

		if(in_array('FeedExpander', class_parents($bridgeName))) {
			$parent_methods = array_merge($parent_methods, get_class_methods('FeedExpander'));
		}

		// Receive all non abstract methods
		$methods = array_diff(get_class_methods($bridgeName), $parent_methods);
		$method_names = implode(', ', $methods);

		$errmsg = $bridgeName
		. ' implements additional public method(s): '
		. $method_names
		. '! Custom functions must be defined in private or protected scope!';

		$this->assertEmpty($method_names, $errmsg);

	}

	private function CheckBridgeGetNameDefaultValue($bridgeName){

		if(in_array('BridgeAbstract', class_parents($bridgeName))) { // Is bridge

			if(!$this->isFunctionMemberOf($bridgeName, 'getName'))
				return;

			$bridge = new $bridgeName();
			$abstract = new BridgeAbstractTest();

			$message = $bridgeName . ': \'getName\' must return a valid name!';

			$this->assertNotEmpty(trim($bridge->getName()), $message);

		}

	}

	// Checks whether the getURI function returns empty or default values
	private function CheckBridgeGetURIDefaultValue($bridgeName){

		if(in_array('BridgeAbstract', class_parents($bridgeName))) { // Is bridge

			if(!$this->isFunctionMemberOf($bridgeName, 'getURI'))
				return;

			$bridge = new $bridgeName();
			$abstract = new BridgeAbstractTest();

			$message = $bridgeName . ': \'getURI\' must return a valid URI!';

			$this->assertNotEmpty(trim($bridge->getURI()), $message);

		}

	}

	private function CheckBridgePublicConstants($bridgeName){

		// Assertion only works for BridgeAbstract!
		if(in_array('BridgeAbstract', class_parents($bridgeName))) {

			$ref = new ReflectionClass($bridgeName);
			$constants = $ref->getConstants();

			$ref = new ReflectionClass('BridgeAbstract');
			$parent_constants = $ref->getConstants();

			foreach($parent_constants as $key => $value) {

				$this->assertArrayHasKey($key, $constants, 'Constant ' . $key . ' missing in ' . $bridgeName);

				// Skip optional constants
				if($key !== 'PARAMETERS' && $key !== 'CACHE_TIMEOUT') {
					$this->assertNotEquals($value, $constants[$key], 'Constant ' . $key . ' missing in ' . $bridgeName);
				}

			}

		}

	}

	private function isFunctionMemberOf($bridgeName, $functionName){

		$bridgeReflector = new ReflectionClass($bridgeName);
		$bridgeMethods = $bridgeReflector->GetMethods();
		$bridgeHasMethod = false;

		foreach($bridgeMethods as $method) {

			if($method->name === $functionName && $method->class === $bridgeReflector->name) {
				return true;
			}

		}

		return false;

	}

	public function testBridgeImplementation($bridgeName){

		require_once('bridges/' . $bridgeName . '.php');

		$this->CheckBridgePublicFunctions($bridgeName);
		$this->CheckBridgePublicConstants($bridgeName);
		$this->CheckBridgeGetNameDefaultValue($bridgeName);
		$this->CheckBridgeGetURIDefaultValue($bridgeName);

	}

	public function count() {
		return count(Bridge::listBridges());
	}

	public function run(TestResult $result = null) {

		if ($result === null) {
			$result = new TestResult;
		}

		foreach (Bridge::listBridges() as $bridge) {

			$bridge .= 'Bridge';

			$result->startTest($this);
			PHP_Timer::start();
			$stopTime = null;

			try {
				$this->testBridgeImplementation($bridge);
			} catch (AssertionFailedError $e) {

				$stopTime = PHP_Timer::stop();
				$result->addFailure($this, $e, $stopTime);

			} catch (Exception $e) {

				$stopTime = PHP_Timer::stop();
				$result->addError($this, $e, $stopTime);

			}

			if ($stopTime === null) {
				$stopTime = PHP_Timer::stop();
			}

			$result->endTest($this, $stopTime);

		}

		return $result;
	}
}

class BridgeAbstractTest extends BridgeAbstract {
	public function collectData(){}
}
