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

    public function testSanitizePathName()
    {
        $this->assertSame('index.php', _sanitize_path_name('/home/satoshi/rss-bridge/index.php', '/home/satoshi/rss-bridge'));
        $this->assertSame('tests/UtilsTest.php', _sanitize_path_name('/home/satoshi/rss-bridge/tests/UtilsTest.php', '/home/satoshi/rss-bridge'));
        $this->assertSame('bug in lib/kek.php', _sanitize_path_name('bug in /home/satoshi/rss-bridge/lib/kek.php', '/home/satoshi/rss-bridge'));
    }

    public function testSanitizePathNameInErrorMessage()
    {
        $raw       = 'Error: Argument 1 passed to foo() must be an instance of kk, string given, called in /home/satoshi/rss-bridge/bridges/RumbleBridge.php';
        $sanitized = 'Error: Argument 1 passed to foo() must be an instance of kk, string given, called in bridges/RumbleBridge.php';
        $this->assertSame($sanitized, _sanitize_path_name($raw, '/home/satoshi/rss-bridge'));
    }

    public function testCreateRandomString()
    {
        $this->assertSame(2, strlen(create_random_string(1)));
        $this->assertSame(4, strlen(create_random_string(2)));
        $this->assertSame(6, strlen(create_random_string(3)));
    }

    public function testUrljoin()
    {
        $base = '/';
        $rel = 'https://example.com/foo';

        $url = urljoin($base, $rel);

        $this->assertSame($rel, $url);
    }
}
