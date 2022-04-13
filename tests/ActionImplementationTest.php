<?php

use PHPUnit\Framework\TestCase;

class ActionImplementationTest extends TestCase {
	private $class;
	private $obj;

	/**
	 * @dataProvider dataActionsProvider
	 */
	public function testClassName($path) {
		$this->setAction($path);
		$this->assertTrue($this->class === ucfirst($this->class), 'class name must start with uppercase character');
		$this->assertEquals(0, substr_count($this->class, ' '), 'class name must not contain spaces');
		$this->assertStringEndsWith('Action', $this->class, 'class name must end with "Action"');
	}

	/**
	 * @dataProvider dataActionsProvider
	 */
	public function testClassType($path) {
		$this->setAction($path);
		$this->assertInstanceOf(ActionInterface::class, $this->obj);
	}

	/**
	 * @dataProvider dataActionsProvider
	 */
	public function testVisibleMethods($path) {
		$allowedActionAbstract = get_class_methods(ActionAbstract::class);
		sort($allowedActionAbstract);

		$this->setAction($path);

		$methods = get_class_methods($this->obj);
		sort($methods);

		$this->assertEquals($allowedActionAbstract, $methods);
	}

	public function dataActionsProvider() {
		$actions = array();
		foreach (glob(PATH_LIB_ACTIONS . '*.php') as $path) {
			$actions[basename($path, '.php')] = array($path);
		}
		return $actions;
	}

	private function setAction($path) {
		$this->class = basename($path, '.php');
		$this->assertTrue(class_exists($this->class), 'class ' . $this->class . ' doesn\'t exist');
		$this->obj = new $this->class();
	}
}
