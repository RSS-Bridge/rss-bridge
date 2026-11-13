<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use Ipv4Address;
use Ipv6Address;
use PHPUnit\Framework\TestCase;

class PickDnsAddressTest extends TestCase
{
    public function testIpv4Only()
    {
        $address = pick_dns_address([
            ['host' => 'example.com', 'type' => 'A', 'ip' => '93.184.216.34'],
        ]);

        $this->assertInstanceOf(Ipv4Address::class, $address);
        $this->assertSame('93.184.216.34', (string) $address);
    }

    public function testIpv6Only()
    {
        $address = pick_dns_address([
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => '2606:4700::1111'],
        ], true);

        $this->assertInstanceOf(Ipv6Address::class, $address);
        $this->assertSame('2606:4700::1111', (string) $address);
    }

    public function testIpv6OnlyIsRejectedWhenIpv6IsDisabled()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No A records found');
        pick_dns_address([
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => '2606:4700::1111'],
        ]);
    }

    public function testAaaaRecordsAreIgnoredWhenIpv6IsDisabled()
    {
        $address = pick_dns_address([
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => '2606:4700::1111'],
            ['host' => 'example.com', 'type' => 'A', 'ip' => '93.184.216.34'],
        ], false);

        $this->assertInstanceOf(Ipv4Address::class, $address);
        $this->assertSame('93.184.216.34', (string) $address);
    }

    public function testIpv4IsPreferredOverIpv6()
    {
        $records = [
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => '2606:4700::1111'],
            ['host' => 'example.com', 'type' => 'A', 'ip' => '93.184.216.34'],
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => '2606:4700::1001'],
        ];

        for ($i = 0; $i < 20; $i++) {
            $address = pick_dns_address($records, true);
            $this->assertInstanceOf(Ipv4Address::class, $address);
            $this->assertSame('93.184.216.34', (string) $address);
        }
    }

    public function testNoAddressRecords()
    {
        $this->expectException(\Exception::class);
        pick_dns_address([
            ['host' => 'example.com', 'type' => 'CNAME', 'target' => 'example.net'],
        ], true);
    }

    public function testMalformedRecordIsRejected()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not a valid ipv6 address');
        pick_dns_address([
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => 'not-an-address'],
        ], true);
    }
}
