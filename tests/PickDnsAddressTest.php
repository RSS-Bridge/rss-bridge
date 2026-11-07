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
        ]);

        $this->assertInstanceOf(Ipv6Address::class, $address);
        $this->assertSame('2606:4700::1111', (string) $address);
    }

    public function testIpv4IsPreferredOverIpv6()
    {
        $records = [
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => '2606:4700::1111'],
            ['host' => 'example.com', 'type' => 'A', 'ip' => '93.184.216.34'],
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => '2606:4700::1001'],
        ];

        for ($i = 0; $i < 20; $i++) {
            $address = pick_dns_address($records);
            $this->assertInstanceOf(Ipv4Address::class, $address);
            $this->assertSame('93.184.216.34', (string) $address);
        }
    }

    public function testNoAddressRecords()
    {
        $this->expectException(\Exception::class);
        pick_dns_address([
            ['host' => 'example.com', 'type' => 'CNAME', 'target' => 'example.net'],
        ]);
    }

    public function testMalformedRecordIsRejected()
    {
        $this->expectException(\Exception::class);
        pick_dns_address([
            ['host' => 'example.com', 'type' => 'AAAA', 'ipv6' => 'not-an-address'],
        ]);
    }
}
