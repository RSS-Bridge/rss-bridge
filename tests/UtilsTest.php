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
}
