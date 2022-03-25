<?php
require_once __DIR__ . '/../lib/rssbridge.php';

use PHPUnit\Framework\TestCase;

class ListActionTest extends TestCase {

	private $action;
	private $data;

	/**
	 * @runInSeparateProcess
	 * @requires function xdebug_get_headers
	 */
	public function testHeaders() {
		$this->initAction();

		$this->assertContains(
			'Content-Type: application/json',
			xdebug_get_headers()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOutput() {
		$this->initAction();

		$items = json_decode($this->data, true);

		$this->assertNotNull($items, 'invalid JSON output: ' . json_last_error_msg());

		$this->assertArrayHasKey('total', $items, 'Missing "total" parameter');
		$this->assertIsInt($items['total'], 'Invalid type');

		$this->assertArrayHasKey('bridges', $items, 'Missing "bridges" array');

		$this->assertEquals(
			$items['total'],
			count($items['bridges']),
			'Item count doesn\'t match'
		);

		$bridgeFac = new BridgeFactory();
		$bridgeFac->setWorkingDir(PATH_LIB_BRIDGES);

		$this->assertEquals(
			count($bridgeFac->getBridgeNames()),
			count($items['bridges']),
			'Number of bridges doesn\'t match'
		);

		$expectedKeys = array(
			'status',
			'uri',
			'name',
			'icon',
			'parameters',
			'maintainer',
			'description'
		);

		$allowedStatus = array(
			'active',
			'inactive'
		);

		foreach($items['bridges'] as $bridge) {
			foreach($expectedKeys as $key) {
				$this->assertArrayHasKey($key, $bridge, 'Missing key "' . $key . '"');
			}

			$this->assertContains($bridge['status'], $allowedStatus, 'Invalid status value');
		}
	}

	////////////////////////////////////////////////////////////////////////////

	private function initAction() {
		$actionFac = new ActionFactory();
		$actionFac->setWorkingDir(PATH_LIB_ACTIONS);

		$this->action = $actionFac->create('list');
		$this->action->setUserData(array()); /* no user data required */

		ob_start();
		$this->action->execute();
		$this->data = ob_get_contents();
		ob_clean();
		ob_end_flush();
	}
}
