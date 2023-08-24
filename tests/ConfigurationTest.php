<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use Configuration;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    public function testValueFromDefaultConfig()
    {
        Configuration::loadConfiguration();
        $this->assertSame(null, Configuration::getConfig('foobar', ''));
        $this->assertSame(null, Configuration::getConfig('foo', 'bar'));
        $this->assertSame('baz', Configuration::getConfig('foo', 'bar', 'baz'));
        $this->assertSame(null, Configuration::getConfig('cache', ''));
        $this->assertSame('UTC', Configuration::getConfig('system', 'timezone'));
    }

    public function testValueFromCustomConfig()
    {
        Configuration::loadConfiguration(['system' => ['timezone' => 'Europe/Berlin']]);
        $this->assertSame('Europe/Berlin', Configuration::getConfig('system', 'timezone'));
    }

    public function testValueFromEnv()
    {
        $env = [
            'RSSBRIDGE_system_timezone' => 'Europe/Berlin',
            'RSSBRIDGE_SYSTEM_MESSAGE' => 'hello',
            'RSSBRIDGE_system_enabled_bridges' => 'TwitterBridge,GettrBridge',
            'RSSBRIDGE_system_enable_debug_mode' => 'true',
            'RSSBRIDGE_fileCache_path' => '/tmp/kek',
        ];
        Configuration::loadConfiguration([], $env);
        $this->assertSame('Europe/Berlin', Configuration::getConfig('system', 'timezone'));
        $this->assertSame('hello', Configuration::getConfig('system', 'message'));
        $this->assertSame(true, Configuration::getConfig('system', 'enable_debug_mode'));
        $this->assertSame('/tmp/kek', Configuration::getConfig('FileCache', 'path'));
        $this->assertSame(['TwitterBridge', 'GettrBridge'], Configuration::getConfig('system', 'enabled_bridges'));
    }
}
