<?php

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
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

        $this->assertNull($sut->loadData());

        $sut->saveData('data');
        $this->assertSame('data', $sut->loadData());
        $this->assertIsNumeric($sut->getTime());
        $sut->purgeCache(-1);

        // Intentionally not deleting the temp folder
    }
}
