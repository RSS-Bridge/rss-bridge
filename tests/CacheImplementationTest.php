<?php

namespace RssBridge\Tests;

use CacheInterface;
use PHPUnit\Framework\TestCase;

class CacheImplementationTest extends TestCase
{
    public function getCacheClassNames()
    {
        $caches = [];
        foreach (glob(PATH_LIB_CACHES . '*.php') as $path) {
            $caches[] = [basename($path, '.php')];
        }
        return $caches;
    }

    /**
     * @dataProvider getCacheClassNames
     */
    public function testClassName($path)
    {
        $this->assertTrue($path === ucfirst($path), 'class name must start with uppercase character');
        $this->assertEquals(0, substr_count($path, ' '), 'class name must not contain spaces');
        $this->assertStringEndsWith('Cache', $path, 'class name must end with "Cache"');
    }

    /**
     * @dataProvider getCacheClassNames
     */
    public function testClassType($path)
    {
        $this->assertTrue(is_subclass_of($path, CacheInterface::class), 'class must be subclass of CacheInterface');
    }
}
