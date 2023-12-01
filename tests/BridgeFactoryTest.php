<?php

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class BridgeFactoryTest extends TestCase
{
    public function testNormalizeBridgeName()
    {
        $this->assertSame('TwitterBridge', \BridgeFactory::normalizeBridgeName('TwitterBridge'));
        $this->assertSame('TwitterBridge', \BridgeFactory::normalizeBridgeName('TwitterBridge.php'));
        $this->assertSame('TwitterBridge', \BridgeFactory::normalizeBridgeName('Twitter'));
//        $this->assertSame('TwitterBridge', $sut->createBridgeClassName('twitterbridge'));
//        $this->assertSame('TwitterBridge', $sut->createBridgeClassName('twitter'));
//        $this->assertSame('TwitterBridge', $sut->createBridgeClassName('tWitTer'));
//        $this->assertSame('TwitterBridge', $sut->createBridgeClassName('TWITTERBRIDGE'));
    }
}
