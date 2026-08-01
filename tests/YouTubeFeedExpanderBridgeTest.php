<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class YouTubeFeedExpanderBridgeTest extends TestCase
{
    public function testParseItem()
    {
        $bridge = new \YouTubeFeedExpanderBridge(new \ArrayCache(), new \NullLogger());
        $bridge->setInput(['channel' => 'UCtest']);

        $item = [
            'uri' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'id' => 'yt:video:dQw4w9WgXcQ',
            'yt' => ['videoId' => 'dQw4w9WgXcQ'],
            'media' => ['group' => ['description' => "line1\nline2"]],
        ];

        $method = new \ReflectionMethod($bridge, 'parseItem');
        $method->setAccessible(true);
        $result = $method->invoke($bridge, $item);

        $this->assertSame(['https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg'], $result['enclosures']);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ#comments', $result['comments']);
        $this->assertSame('yt:video:dQw4w9WgXcQ', $result['uid']);
        $this->assertStringContainsString('line1<br>line2', $result['content']);
        $this->assertArrayNotHasKey('media', $result);
    }

    public function testHideShorts()
    {
        $bridge = new \YouTubeFeedExpanderBridge(new \ArrayCache(), new \NullLogger());
        $bridge->setInput(['channel' => 'UCtest', 'hideshorts' => 'on']);

        $method = new \ReflectionMethod($bridge, 'parseItem');
        $method->setAccessible(true);

        $shortsItem = [
            'uri' => 'https://www.youtube.com/shorts/abc123',
            'id' => 'yt:video:abc123',
            'yt' => ['videoId' => 'abc123'],
            'media' => ['group' => ['description' => 'A short']],
        ];
        $this->assertNull($method->invoke($bridge, $shortsItem));

        $regularItem = [
            'uri' => 'https://www.youtube.com/watch?v=abc123',
            'id' => 'yt:video:abc123',
            'yt' => ['videoId' => 'abc123'],
            'media' => ['group' => ['description' => 'A video']],
        ];
        $this->assertNotNull($method->invoke($bridge, $regularItem));
    }

    public function testEmbedUrl()
    {
        $method = new \ReflectionMethod(\YouTubeFeedExpanderBridge::class, 'parseItem');
        $method->setAccessible(true);

        $item = [
            'uri' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'id' => 'yt:video:dQw4w9WgXcQ',
            'yt' => ['videoId' => 'dQw4w9WgXcQ'],
            'media' => ['group' => ['description' => 'A video']],
        ];

        $bridge = new \YouTubeFeedExpanderBridge(new \ArrayCache(), new \NullLogger());
        $bridge->setInput(['channel' => 'UCtest', 'embedurl' => 'on']);
        $result = $method->invoke($bridge, $item);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $result['uri']);

        $bridge = new \YouTubeFeedExpanderBridge(new \ArrayCache(), new \NullLogger());
        $bridge->setInput(['channel' => 'UCtest', 'embedurl' => 'on', 'nocookie' => 'on']);
        $result = $method->invoke($bridge, $item);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $result['uri']);
    }

    public function testGetIconCache()
    {
        $cache = new \ArrayCache();
        $bridge = new \YouTubeFeedExpanderBridge($cache, new \NullLogger());
        $bridge->setInput(['channel' => 'UCtest']);

        $cache->set('YouTubeFeedExpanderBridge_icon_UCtest', 'https://example.com/icon.jpg');
        $this->assertSame('https://example.com/icon.jpg', $bridge->getIcon());
    }

    public function testGetIconFallback()
    {
        $bridge = new \YouTubeFeedExpanderBridge(new \ArrayCache(), new \NullLogger());
        $this->assertSame('https://www.youtube.com/favicon.ico', $bridge->getIcon());
    }
}
