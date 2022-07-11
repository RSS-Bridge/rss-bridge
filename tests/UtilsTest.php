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
}
