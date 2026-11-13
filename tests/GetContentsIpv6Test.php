<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use Configuration;
use HttpClient;
use NullCache;
use PHPUnit\Framework\TestCase;
use Response;

/**
 * Literal addresses need no dns lookup, so getContents() can be tested
 * with a stub http client.
 */
class GetContentsIpv6Test extends TestCase
{
    private $previousContainer;
    private $httpClient;

    protected function setUp(): void
    {
        $this->previousContainer = $GLOBALS['container'] ?? null;
        $this->httpClient = new class implements HttpClient {
            public array $requests = [];

            public function request(string $url, array $config = []): Response
            {
                $this->requests[] = ['url' => $url, 'config' => $config];
                return new Response('ok');
            }
        };
        $GLOBALS['container'] = [
            'http_client' => $this->httpClient,
            'cache' => new NullCache(),
        ];
    }

    protected function tearDown(): void
    {
        $GLOBALS['container'] = $this->previousContainer;
        Configuration::loadConfiguration();
    }

    public function testLiteralIpv6IsRejectedWhenIpv6IsDisabled()
    {
        Configuration::loadConfiguration();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid URL');
        try {
            getContents('http://[2001:db8::1]/feed');
        } finally {
            $this->assertSame([], $this->httpClient->requests);
        }
    }

    public function testPublicLiteralIpv6()
    {
        Configuration::loadConfiguration(['http' => ['enable_ipv6' => true]]);

        $this->assertSame('ok', getContents('http://[2001:db8::1]:8080/feed'));

        $this->assertCount(1, $this->httpClient->requests);
        $this->assertSame('http://[2001:db8::1]:8080/feed', $this->httpClient->requests[0]['url']);
        // No dns lookup, so nothing to pin
        $this->assertSame([], $this->httpClient->requests[0]['config']['dns']);
    }

    public function testLiteralIpv4StillWorks()
    {
        Configuration::loadConfiguration(['http' => ['enable_ipv6' => true]]);

        $this->assertSame('ok', getContents('http://93.184.216.34/feed'));
        $this->assertCount(1, $this->httpClient->requests);
    }

    public function blockedLiteralProvider(): array
    {
        return [
            'loopback' => ['http://[::1]/', 'Reserved addresses disallowed'],
            'unspecified' => ['http://[::]/', 'Reserved addresses disallowed'],
            'link local' => ['http://[fe80::1]/', 'Reserved addresses disallowed'],
            'ipv4-mapped loopback' => ['http://[::ffff:127.0.0.1]/', 'Reserved addresses disallowed'],
            'ipv4-mapped metadata' => ['http://[::ffff:169.254.169.254]/', 'Reserved addresses disallowed'],
            'unique local' => ['http://[fc00::1]/', 'Private addresses disallowed'],
            'ipv4-mapped private' => ['http://[::ffff:10.0.0.1]/', 'Private addresses disallowed'],
        ];
    }

    /**
     * @dataProvider blockedLiteralProvider
     */
    public function testBlockedLiteralIpv6(string $url, string $message)
    {
        Configuration::loadConfiguration(['http' => ['enable_ipv6' => true]]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($message);
        try {
            getContents($url);
        } finally {
            $this->assertSame([], $this->httpClient->requests);
        }
    }

    public function testBlockedLiteralIpv6IsAllowedByTheAddressSettings()
    {
        Configuration::loadConfiguration(['http' => [
            'enable_ipv6' => true,
            'allow_private_addresses' => true,
            'allow_reserved_addresses' => true,
        ]]);

        $this->assertSame('ok', getContents('http://[::1]/'));
        $this->assertSame('ok', getContents('http://[fc00::1]/'));
        $this->assertCount(2, $this->httpClient->requests);
    }
}
