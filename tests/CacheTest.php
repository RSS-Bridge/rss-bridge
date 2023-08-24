<?php

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function testConfig()
    {
        $sut = new \FileCache(['path' => '/tmp/']);
        $this->assertSame(['path' => '/tmp/', 'enable_purge' => true], $sut->getConfig());

        $sut = new \FileCache(['path' => '/', 'enable_purge' => false]);
        $this->assertSame(['path' => '/', 'enable_purge' => false], $sut->getConfig());

        $sut = new \FileCache(['path' => '/tmp', 'enable_purge' => true]);
        $this->assertSame(['path' => '/tmp/', 'enable_purge' => true], $sut->getConfig());
    }

    public function testFileCache()
    {
        $temporaryFolder = sprintf('%s/rss_bridge_%s/', sys_get_temp_dir(), create_random_string());
        mkdir($temporaryFolder);

        $sut = new \FileCache([
            'path' => $temporaryFolder,
            'enable_purge' => true,
        ]);
        $sut->setScope('scope');
        $sut->purgeCache(-1);
        $sut->setKey(['key']);

        $this->assertNull($sut->getTime());
        $this->assertNull($sut->loadData());

        $sut->saveData('data');
        $this->assertSame('data', $sut->loadData());
        $this->assertIsNumeric($sut->getTime());
        $sut->purgeCache(-1);

        // Intentionally not deleting the temp folder
    }
}
