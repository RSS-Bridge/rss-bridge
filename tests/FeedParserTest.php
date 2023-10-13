<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class FeedParserTest extends TestCase
{
    public function testRss1()
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="utf-8"?> 
        <rdf:RDF 
          xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
          xmlns:cc="http://creativecommons.org/ns#"
          xmlns="http://purl.org/rss/1.0/"
        > 
        <channel rdf:about="http://meerkat.oreillynet.com/?_fl=rss1.0">
            <title>hello feed</title>
            <link>http://meerkat.oreillynet.com</link>
            <description>Meerkat: An Open Wire Service</description>
            
            <items>
                <rdf:Seq>
                    <rdf:li resource="http://c.moreover.com/click/here.pl?r123" />
                </rdf:Seq>
            </items>
        </channel>

        <item rdf:about="http://c.moreover.com/click/here.pl?r123">
            <title>XML: A Disruptive Technology</title> 
            <link>http://c.moreover.com/click/here.pl?r123</link>
            <description>desc</description>
        </item> 
        </rdf:RDF>
        XML;

        $sut = new \FeedParser();
        $feed = $sut->parseFeed($xml);

        $this->assertSame('hello feed', $feed['title']);
        $this->assertSame('http://meerkat.oreillynet.com', $feed['uri']);
        $this->assertSame(null, $feed['icon']);

        $item = $feed['items'][0];
        $this->assertSame('XML: A Disruptive Technology', $item['title']);
        $this->assertSame('http://c.moreover.com/click/here.pl?r123', $item['uri']);
        $this->assertSame('desc', $item['content']);
    }

    public function testRss2()
    {
        $xml = <<<XML
        <?xml version="1.0"?>
        <rss version="2.0">
            <channel>
                <title>hello feed</title>
                <link>https://example.com/</link>
                <image>
                    <url>https://example.com/2.ico</url>
                </image>

                <item>
                    <title>hello world</title>
                    <link>https://example.com/1</link>
                    <description>desc2</description>
                    <pubDate>Tue, 26 Apr 2022 00:00:00 +0200</pubDate>
                    <author>root</author>
                    <enclosure url="https://example.com/1.png"></enclosure>
                </item>
            </channel>
        </rss>
        XML;

        $sut = new \FeedParser();
        $feed = $sut->parseFeed($xml);

        $this->assertSame('hello feed', $feed['title']);
        $this->assertSame('https://example.com/', $feed['uri']);
        $this->assertSame('https://example.com/2.ico', $feed['icon']);

        $item = $feed['items'][0];
        $this->assertSame('hello world', $item['title']);
        $this->assertSame('https://example.com/1', $item['uri']);
        $this->assertSame(1650924000, $item['timestamp']);
        $this->assertSame('root', $item['author']);
        $this->assertSame('desc2', $item['content']);
        $this->assertSame(['https://example.com/1.png'], $item['enclosures']);
    }

    public function testAtom()
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
            <title>hello feed</title>
            <link href="https://example.com/1"></link>
            <icon>https://example.com/2.ico</icon>

            <entry>
                <title>hello world</title>
                <link href="https://example.com/1"></link>
                <author>
                    <name>root</name>
                </author>
                <content type="html">html</content>
                <updated>2015-11-05T14:38:49+01:00</updated>
            </entry>
        </feed>
        XML;

        $sut = new \FeedParser();
        $feed = $sut->parseFeed($xml);

        $this->assertSame('hello feed', $feed['title']);
        $this->assertSame('https://example.com/1', $feed['uri']);
        $this->assertSame('https://example.com/2.ico', $feed['icon']);

        $item = $feed['items'][0];
        $this->assertSame('hello world', $item['title']);
        $this->assertSame('https://example.com/1', $item['uri']);
        $this->assertSame(1446730729, $item['timestamp']);
        $this->assertSame('root', $item['author']);
        $this->assertSame('html', $item['content']);
    }
}
