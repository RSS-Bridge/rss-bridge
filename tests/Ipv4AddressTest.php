<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use Ipv4Address;
use PHPUnit\Framework\TestCase;

class Ipv4AddressTest extends TestCase
{
    public function test()
    {
        $reserved = [
            '127.0.0.1',
            '169.254.1.1',
        ];
        $private = [
            '10.1.1.1',
            '192.168.1.1',
            '172.16.1.1',
        ];
        $public = [
            '11.1.1.1',
            '192.169.1.1',
            '172.115.1.1',
        ];

        foreach ($reserved as $ip) {
            $this->assertTrue(Ipv4Address::fromString($ip)->isReserved());
        }

        foreach ($private as $ip) {
            $this->assertTrue(Ipv4Address::fromString($ip)->isPrivate());
        }

        foreach ($public as $ip) {
            $this->assertTrue(Ipv4Address::fromString($ip)->isPublic());
        }
    }
}