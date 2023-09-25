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
        $this->assertSame('aa', $item->getTitle());
        $this->assertSame('aa', $item->title);
    }
}
