<?php

use PHPUnit\Framework\TestCase;

class CacheImplementationTest extends TestCase {
	private $class;

	/**
	 * @dataProvider dataCachesProvider
	 */
	public function testClassName($path) {
		$this->setCache($path);
		$this->assertTrue($this->class === ucfirst($this->class), 'class name must start with uppercase character');
		$this->assertEquals(0, substr_count($this->class, ' '), 'class name must not contain spaces');
		$this->assertStringEndsWith('Cache', $this->class, 'class name must end with "Cache"');
	}

	/**
	 * @dataProvider dataCachesProvider
	 */
	public function testClassType($path) {
		$this->setCache($path);
		$this->assertTrue(is_subclass_of($this->class, CacheInterface::class), 'class must be subclass of CacheInterface');
	}

	////////////////////////////////////////////////////////////////////////////

	public function dataCachesProvider() {
		$caches = array();
		foreach (glob(PATH_LIB_CACHES . '*.php') as $path) {
			$caches[basename($path, '.php')] = array($path);
		}
		return $caches;
	}

	private function setCache($path) {
		require_once $path;
		$this->class = basename($path, '.php');
		$this->assertTrue(class_exists($this->class), 'class ' . $this->class . ' doesn\'t exist');
	}
}
