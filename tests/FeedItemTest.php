<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use FeedItem;
use PHPUnit\Framework\TestCase;

class FeedItemTest extends TestCase
{
    public function test()
    {
        $item = new FeedItem();
        $item->setTitle('hello');
        $this->assertSame('hello', $item->getTitle());

        $item = FeedItem::fromArray(['title' => 'hello2']);
        $this->assertSame('hello2', $item->getTitle());

        $item = new FeedItem();
        $item->setAuthor('123');
        $this->assertSame('123', $item->getAuthor());

        $item = new FeedItem();
        $item->title = 'aa';
        $this->assertSame('aa', $item->title);
        $this->assertSame('aa', $item->getTitle());
    }

    public function testTimestamp()
    {
        $item = new FeedItem();
        $item->setTimestamp(5);
        $this->assertSame(5, $item->getTimestamp());

        $item->setTimestamp('5');
        $this->assertSame(5, $item->getTimestamp());

        $item->setTimestamp('1970-01-01 18:00:00');
        $this->assertSame(64800, $item->getTimestamp());

        $item->setTimestamp('1st jan last year');

        // This will fail at 2025-01-01 hehe
        $this->assertSame(1672531200, $item->getTimestamp());
    }
}
