<?php
require_once __DIR__ . '/../lib/rssbridge.php';

use PHPUnit\Framework\TestCase;

class FormatImplementationTest extends TestCase {
	private $class;
	private $obj;

	/**
	 * @dataProvider dataFormatsProvider
	 */
	public function testClassName($path) {
		$this->setFormat($path);
		$this->assertTrue($this->class === ucfirst($this->class), 'class name must start with uppercase character');
		$this->assertEquals(0, substr_count($this->class, ' '), 'class name must not contain spaces');
		$this->assertStringEndsWith('Format', $this->class, 'class name must end with "Format"');
	}

	/**
	 * @dataProvider dataFormatsProvider
	 */
	public function testClassType($path) {
		$this->setFormat($path);
		$this->assertInstanceOf(FormatInterface::class, $this->obj);
	}

	////////////////////////////////////////////////////////////////////////////

	public function dataFormatsProvider() {
		$formats = array();
		foreach (glob(PATH_LIB_FORMATS . '*.php') as $path) {
			$formats[basename($path, '.php')] = array($path);
		}
		return $formats;
	}

	private function setFormat($path) {
		require_once $path;
		$this->class = basename($path, '.php');
		$this->assertTrue(class_exists($this->class), 'class ' . $this->class . ' doesn\'t exist');
		$this->obj = new $this->class();
	}
}
