<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class FeedItemTest extends TestCase
{
    public function test()
    {
        $item = [
            'title' => 'kek',
        ];
        $feedItem = \FeedItem::fromArray($item);
        $this->assertSame('kek', $feedItem->getTitle());
    }
}
