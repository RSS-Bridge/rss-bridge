<?php

namespace RssBridge\Tests\Bridges;

use PHPUnit\Framework\TestCase;

class MastodonBridgeTest extends TestCase
{
    public function test()
    {
        \Configuration::loadConfiguration(['cache' => ['type' => 'null']]);
        $b = new \MastodonBridge();
        // https://bird.makeup/users/asmongold/remote_follow
        $b->setDatas(['canusername' => '@asmongold@bird.makeup']);
        $b->collectData();
    }
}
