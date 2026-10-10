<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;
use Url;

class UrlTest extends TestCase
{
    public function testBasicUsage()
    {
        $sut = Url::fromString('http://example.com/qqq?foo=bar');

        $this->assertSame('http', $sut->getScheme());
        $this->assertSame('example.com', $sut->getHost());
        $this->assertSame('/qqq', $sut->getPath());
        $this->assertSame('foo=bar', $sut->getQueryString());

        $sut = Url::fromString('http://example.com/qqq');
        $this->assertNull($sut->getQueryString());
    }

    public function testNormalization()
    {
        $urls = [
            'http://example.com' => 'http://example.com/',
            'http://example.com//' => 'http://example.com/',
            'http://example.com///' => 'http://example.com/',
            'https://example.com/?' => 'https://example.com/',
            'https://example.com/foo?' => 'https://example.com/foo',
            'http://example.com:80/' => 'http://example.com/',
        ];
        foreach ($urls as $from => $to) {
            $this->assertSame($to, Url::fromString($from)->normalize());
        }
    }

    public function testMutation()
    {
        $this->assertSame('http://example.com/foo', (Url::fromString('http://example.com/'))->withPath('/foo')->__toString());
        $this->assertSame('http://example.com/foo?a=b', (Url::fromString('http://example.com/?a=b'))->withPath('/foo')->__toString());
        $this->assertSame('http://example.com/', (Url::fromString('http://example.com/'))->withPath('/')->__toString());
        $this->assertSame('http://example.com/qqq?foo=bar', (Url::fromString('http://example.com/qqq'))->withQueryString('foo=bar')->__toString());
        $this->assertSame('http://example.net/qqq?foo=bar', (Url::fromString('http://example.com/qqq?foo=bar'))->withHost('example.net')->__toString());
    }

    public function testNormalizeScheme()
    {
        $this->assertSame('http://example.com', Url::normalizeScheme('http://example.com'));
        $this->assertSame('http://example.com', Url::normalizeScheme('example.com'));
    }
}
