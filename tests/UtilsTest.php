<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
    public function testTruncate()
    {
        $this->assertSame('f...', truncate('foo', 1));
        $this->assertSame('fo...', truncate('foo', 2));
        $this->assertSame('foo', truncate('foo', 3));
        $this->assertSame('foo', truncate('foo', 4));
        $this->assertSame('fo[...]', truncate('foo', 2, '[...]'));
    }

    public function testFormatBytes()
    {
        $this->assertSame('1 B', format_bytes(1));
        $this->assertSame('1 KB', format_bytes(1024));
        $this->assertSame('1 MB', format_bytes(1024 ** 2));
        $this->assertSame('1 GB', format_bytes(1024 ** 3));
        $this->assertSame('1 TB', format_bytes(1024 ** 4));
    }

    public function testFileCache()
    {
        $sut = new \FileCache();
        $sut->setScope('scope');
        $sut->purgeCache(-1);
        $sut->setKey(['key']);

        $this->assertNull($sut->loadData());

        $sut->saveData('data');
        $this->assertSame('data', $sut->loadData());
        $this->assertIsNumeric($sut->getTime());
        $sut->purgeCache(-1);
    }

    public function testTrimFilePath()
    {
        $this->assertSame('', trim_path_prefix(dirname(__DIR__)));
        $this->assertSame('tests', trim_path_prefix(__DIR__));
        $this->assertSame('tests/UtilsTest.php', trim_path_prefix(__DIR__ . '/UtilsTest.php'));
    }
}
