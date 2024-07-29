<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function testBridge()
    {
        $sut = new \MrssFormat();

        $expected = [
            'name'          => '',
            'uri'           => '',
            'icon'          => '',
            'donationUri'   => '',
        ];
        $this->assertEquals([], $sut->getFeed());

        $sut->setFeed([
            'name'          => '0',
            'uri'           => '1',
            'icon'          => '2',
            'donationUri'   => '3',
        ]);
        $expected = [
            'name'          => '0',
            'uri'           => '1',
            'icon'          => '2',
            'donationUri'   => '3',
        ];
        $this->assertEquals($expected, $sut->getFeed());

        $sut->setFeed([]);
        $expected = [
            'name'          => '',
            'uri'           => '',
            'icon'          => '',
            'donationUri'   => '',
        ];
        $this->assertEquals($expected, $sut->getFeed());

        $sut->setFeed(['foo' => 'bar', 'foo2' => 'bar2']);
        $expected = [
            'name'          => '',
            'uri'           => '',
            'icon'          => '',
            'donationUri'   => '',
            'foo'           => 'bar',
            'foo2'          => 'bar2',
        ];
        $this->assertEquals($expected, $sut->getFeed());
    }
}

class TestFormat extends \FormatAbstract
{
    public function stringify(?\Request $request)
    {
    }
}

class TestBridge extends \BridgeAbstract
{
    public function collectData()
    {
        $this->items[] = ['title' => 'kek'];
    }
}
