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
        $this->assertSame(80, $sut->getPort());
        $this->assertSame('/qqq', $sut->getPath());
        $this->assertSame('foo=bar', $sut->getQueryString());

        $sut = Url::fromString('http://example.com/qqq');
        $this->assertNull($sut->getQueryString());

        $sut = Url::fromString('https://example.com/qqq?foo=bar');
        $this->assertSame(443, $sut->getPort());
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

    public function testLiteralIpv6IsRejectedByDefault()
    {
        $this->assertFalse(Url::validate('http://[2001:db8::1]/feed'));
        $this->assertFalse(Url::validate('http://[::1]/'));

        $this->expectException(\UrlException::class);
        Url::fromString('http://[2001:db8::1]/feed');
    }

    public function testLiteralIpv6()
    {
        $this->assertTrue(Url::validate('http://[2001:db8::1]', true));
        $this->assertTrue(Url::validate('http://[2001:db8::1]/feed', true));
        $this->assertTrue(Url::validate('https://[2001:db8::1]:8443/feed?a=b', true));
        $this->assertTrue(Url::validate('http://[::ffff:127.0.0.1]/', true));
        $this->assertTrue(Url::validate('http://example.com/', true));

        $this->assertFalse(Url::validate('http://[2001:db8::g]/', true));
        $this->assertFalse(Url::validate('http://[127.0.0.1]/', true));
        $this->assertFalse(Url::validate('http://[]/', true));
        $this->assertFalse(Url::validate('http://[fe80::1%25eth0]/', true));
        $this->assertFalse(Url::validate('http://2001:db8::1/', true));
        $this->assertFalse(Url::validate('http://[2001:db8::1]foo/', true));

        $sut = Url::fromString('https://[2001:db8::1]:8443/feed?a=b', true);
        $this->assertSame('[2001:db8::1]', $sut->getHost());
        $this->assertSame(8443, $sut->getPort());
        $this->assertSame('/feed', $sut->getPath());
        $this->assertSame('https://[2001:db8::1]:8443/feed?a=b', $sut->normalize());
        $this->assertSame('http://[2001:db8::1]/', Url::fromString('http://[2001:db8::1]', true)->normalize());
    }
}
