<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RedditBridgeTest extends TestCase
{
    public function test()
    {
        $sut = new RedditBridge(new NullCache(), new NullLogger());

        // https://old.reddit.com/search.json?q=cats dogs hen subreddit:php&sort=hot&include_over_18=on
        $expected = 'https://old.reddit.com/search.json?q=cats+dogs+hen+subreddit%3Aphp&sort=hot&include_over_18=on&t=all';
        $actual = RedditBridge::createUrl('cats,dogs hen', '', 'php', false, 'hot', 'all', 'single');
        $this->assertSame($expected, $actual);

        // https://old.reddit.com/search.json?q=author:RavenousRandy&sort=hot&include_over_18=on
        $expected = 'https://old.reddit.com/search.json?q=author%3ARavenousRandy&sort=hot&include_over_18=on&t=week';
        $actual = RedditBridge::createUrl('', '', 'RavenousRandy', true, 'hot', 'week', 'user');
        $this->assertSame($expected, $actual);

        // https://old.reddit.com/search.json?q=cats dogs hen flair:"Proxy" subreddit:php&sort=hot&include_over_18=on
        $expected = 'https://old.reddit.com/search.json?q=cats+dogs+hen+flair%3A%22Proxy%22+subreddit%3Aphp&sort=hot&include_over_18=on&t=month';
        $actual = RedditBridge::createUrl('cats,dogs hen', 'Proxy', 'php', false, 'hot', 'month', 'single');
        $this->assertSame($expected, $actual);

        // https://old.reddit.com/search.json?q=cats dogs hen flair:"Proxy Linux Server" subreddit:php&sort=hot&include_over_18=on
        $expected = 'https://old.reddit.com/search.json?q=cats+dogs+hen+flair%3A%22Proxy+Linux+Server%22+subreddit%3Aphp&sort=hot&include_over_18=on&t=day';
        $actual = RedditBridge::createUrl('cats,dogs hen', 'Proxy,Linux Server', 'php', false, 'hot', 'day', 'single');
        $this->assertSame($expected, $actual);
    }
}
