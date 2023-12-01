<?php

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function testConfig()
    {
        $sut = new \FileCache(new \NullLogger(), ['path' => '/tmp/']);
        $this->assertSame(['path' => '/tmp/', 'enable_purge' => true], $sut->getConfig());

        $sut = new \FileCache(new \NullLogger(), ['path' => '/', 'enable_purge' => false]);
        $this->assertSame(['path' => '/', 'enable_purge' => false], $sut->getConfig());

        $sut = new \FileCache(new \NullLogger(), ['path' => '/tmp', 'enable_purge' => true]);
        $this->assertSame(['path' => '/tmp/', 'enable_purge' => true], $sut->getConfig());
    }

    public function testFileCache()
    {
        $temporaryFolder = sprintf('%s/rss_bridge_%s/', sys_get_temp_dir(), create_random_string());
        mkdir($temporaryFolder);

        $sut = new \FileCache(new \NullLogger(), [
            'path' => $temporaryFolder,
            'enable_purge' => true,
        ]);
        $sut->clear();

        $this->assertNull($sut->get('key'));

        $sut->set('key', 'data', 5);
        $this->assertSame('data', $sut->get('key'));
        $sut->clear();

        // Intentionally not deleting the temp folder
    }
}
