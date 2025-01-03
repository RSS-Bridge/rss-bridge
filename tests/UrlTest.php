<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;
use Url;

class UrlTest extends TestCase
{
    public function testBasicUsages()
    {
        $urls = [
            'http://example.com/',
            'http://example.com:9000/',
            'https://example.com/',
            'https://example.com/?foo',
            'https://example.com/?foo=bar',
        ];
        foreach ($urls as $url) {
            $this->assertSame($url, Url::fromString($url)->__toString());
        }
    }

    public function testNormalization()
    {
        $urls = [
            'http://example.com' => 'http://example.com/',
            'https://example.com/?' => 'https://example.com/',
            'https://example.com/foo?' => 'https://example.com/foo',
            'http://example.com:80/' => 'http://example.com/',
        ];
        foreach ($urls as $from => $to) {
            $this->assertSame($to, Url::fromString($from)->__toString());
        }
    }

    public function testIllegalPath()
    {
        $this->expectException(\UrlException::class);
        Url::fromString('https://example.com//foo');
    }

    public function testMutation()
    {
        $this->assertSame('http://example.com/foo', (Url::fromString('http://example.com/'))->withPath('/foo')->__toString());
        $this->assertSame('http://example.com/foo?a=b', (Url::fromString('http://example.com/?a=b'))->withPath('/foo')->__toString());
        $this->assertSame('http://example.com/', (Url::fromString('http://example.com/'))->withPath('/')->__toString());
        $this->assertSame('http://example.com/qqq?foo=bar', (Url::fromString('http://example.com/qqq'))->withQueryString('foo=bar')->__toString());
        $this->assertSame('http://example.net/qqq?foo=bar', (Url::fromString('http://example.com/qqq?foo=bar'))->withHost('example.net')->__toString());
    }
}
