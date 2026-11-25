<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use Ipv6Address;
use PHPUnit\Framework\TestCase;

class Ipv6AddressTest extends TestCase
{
    public function testValidate()
    {
        $this->assertTrue(Ipv6Address::validate('2606:4700::1111'));
        $this->assertTrue(Ipv6Address::validate('::1'));
        $this->assertTrue(Ipv6Address::validate('::ffff:127.0.0.1'));
        $this->assertFalse(Ipv6Address::validate('127.0.0.1'));
        $this->assertFalse(Ipv6Address::validate('[::1]'));
        $this->assertFalse(Ipv6Address::validate('example.com'));
    }

    public function testFromStringRejectsIpv4()
    {
        $this->expectException(\Exception::class);
        Ipv6Address::fromString('127.0.0.1');
    }

    public function test()
    {
        $reserved = [
            '::',
            '::1',
            'fe80::1',
            'febf::1',
            'fec0::1',
            // ipv4-mapped, ipv4-compatible and nat64 addresses embedding a reserved ipv4 address
            '::ffff:127.0.0.1',
            '::ffff:7f00:1',
            '::ffff:169.254.169.254',
            '::127.0.0.1',
            '::7f00:1',
            '64:ff9b::7f00:1',
            '64:ff9b::127.0.0.1',
        ];
        $private = [
            'fc00::1',
            'fd12:3456:789a::1',
            // embedding a private ipv4 address
            '::ffff:10.1.1.1',
            '::ffff:c0a8:101',
            '::ffff:172.16.1.1',
            '64:ff9b::192.168.1.1',
        ];
        $public = [
            '2606:4700::1111',
            '2001:4860:4860::8888',
            '2a00:1450:4001:81b::200e',
            // just outside the unique local and link local ranges
            'fbff::1',
            'fe7f::1',
            // embedding a public ipv4 address
            '::ffff:8.8.8.8',
            '::ffff:808:808',
            '64:ff9b::808:808',
        ];

        foreach ($reserved as $ip) {
            $this->assertTrue(Ipv6Address::fromString($ip)->isReserved(), $ip);
            $this->assertFalse(Ipv6Address::fromString($ip)->isPublic(), $ip);
        }

        foreach ($private as $ip) {
            $this->assertTrue(Ipv6Address::fromString($ip)->isPrivate(), $ip);
            $this->assertFalse(Ipv6Address::fromString($ip)->isPublic(), $ip);
        }

        foreach ($public as $ip) {
            $this->assertFalse(Ipv6Address::fromString($ip)->isPrivate(), $ip);
            $this->assertFalse(Ipv6Address::fromString($ip)->isReserved(), $ip);
            $this->assertTrue(Ipv6Address::fromString($ip)->isPublic(), $ip);
        }
    }
}
