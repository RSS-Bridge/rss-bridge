<?php

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class BridgeFactoryTest extends TestCase
{
    public function setUp(): void
    {
        \Configuration::loadConfiguration();
    }

    public function testNormalizeBridgeName()
    {
        $this->assertSame('TwitterBridge', \BridgeFactory::normalizeBridgeName('TwitterBridge'));
        $this->assertSame('TwitterBridge', \BridgeFactory::normalizeBridgeName('TwitterBridge.php'));
        $this->assertSame('TwitterBridge', \BridgeFactory::normalizeBridgeName('Twitter'));
    }

    public function testSanitizeBridgeName()
    {
        $sut = new \BridgeFactory();

        $this->assertSame('TwitterBridge', $sut->createBridgeClassName('twitterbridge'));
        $this->assertSame('TwitterBridge', $sut->createBridgeClassName('twitter'));
        $this->assertSame('TwitterBridge', $sut->createBridgeClassName('tWitTer'));
        $this->assertSame('TwitterBridge', $sut->createBridgeClassName('TWITTERBRIDGE'));
    }
}
