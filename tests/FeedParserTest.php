<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class FeedParserTest extends TestCase
{
    private \FeedParser $sut;

    public function setUp(): void
    {
        $this->sut = new \FeedParser();
    }

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

        $feed = $this->sut->parseFeed($xml);

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

        $feed = $this->sut->parseFeed($xml);

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

        $feed = $this->sut->parseFeed($xml);

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

    public function testAppleItunesModule()
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss
            version="2.0"
            xmlns:atom="http://www.w3.org/2005/Atom"
            xmlns:cc="http://web.resource.org/cc/"
            xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
            xmlns:media="http://search.yahoo.com/mrss/"
            xmlns:content="http://purl.org/rss/1.0/modules/content/"
            xmlns:podcast="https://podcastindex.org/namespace/1.0"
            xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
            xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
        >
            <channel>

                <item>
                    <itunes:duration>30:05</itunes:duration>
                    <enclosure length="48123248" type="audio/mpeg" url="https://example.com/1.mp3" />
                </item>
            </channel>
        </rss>
        XML;

        $feed = $this->sut->parseFeed($xml);
        $expected = [
            'title' => '',
            'uri' => '',
            'icon' => '',
            'items' => [
                [
                    'uri' => '',
                    'title' => '',
                    'content' => '',
                    'timestamp' => '',
                    'author' => '',
                    'itunes' => [
                        'duration' => '30:05',
                    ],
                    'enclosure' => [
                        'url' => 'https://example.com/1.mp3',
                        'length' => '48123248',
                        'type' => 'audio/mpeg',
                    ],
                    'enclosures' => [
                        'https://example.com/1.mp3',
                    ],
                ]
            ],
        ];
        $this->assertEquals($expected, $feed);
    }

    public function testYoutubeMediaModule()
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns:media="http://search.yahoo.com/mrss/" xmlns="http://www.w3.org/2005/Atom">
         <link rel="self" href="http://www.youtube.com/feeds/videos.xml?channel_id=UCuCkxoKLYO_EQ2GeFtbM_bw"/>
         <id>yt:channel:uCkxoKLYO_EQ2GeFtbM_bw</id>
         <yt:channelId>uCkxoKLYO_EQ2GeFtbM_bw</yt:channelId>
         <title>Half as Interesting</title>
         <link rel="alternate" href="https://www.youtube.com/channel/UCuCkxoKLYO_EQ2GeFtbM_bw"/>
         <author>
          <name>Half as Interesting</name>
          <uri>https://www.youtube.com/channel/UCuCkxoKLYO_EQ2GeFtbM_bw</uri>
         </author>
         <published>2017-08-26T20:06:05+00:00</published>
         <entry>
          <id>yt:video:Upjg7F28DJw</id>
          <yt:videoId>Upjg7F28DJw</yt:videoId>
          <yt:channelId>UCuCkxoKLYO_EQ2GeFtbM_bw</yt:channelId>
          <title>The Nuke-Proof US Military Base in a Mountain</title>
          <link rel="alternate" href="https://www.youtube.com/watch?v=Upjg7F28DJw"/>
          <author>
           <name>Half as Interesting</name>
           <uri>https://www.youtube.com/channel/UCuCkxoKLYO_EQ2GeFtbM_bw</uri>
          </author>
          <published>2025-01-24T15:44:18+00:00</published>
          <updated>2025-01-25T06:55:19+00:00</updated>
          <media:group>
           <media:title>The Nuke-Proof US Military Base in a Mountain</media:title>
           <media:content url="https://www.youtube.com/v/Upjg7F28DJw?version=3" type="application/x-shockwave-flash" width="640" height="390"/>
           <media:thumbnail url="https://i2.ytimg.com/vi/Upjg7F28DJw/hqdefault.jpg" width="480" height="360"/>
           <media:description>Receive 10% off anything on bellroy.com: https://bit.ly/3HdOWu9</media:description>
           <media:community>
            <media:starRating count="10157" average="5.00" min="1" max="5"/>
            <media:statistics views="228462"/>
           </media:community>
          </media:group>
         </entry>
        </feed>
        XML;

        $feed = $this->sut->parseFeed($xml);
        $expected = [
            'title' => 'Half as Interesting',
            'uri' => 'https://www.youtube.com/channel/UCuCkxoKLYO_EQ2GeFtbM_bw',
            'icon' => null,
            'items' => [
                [
                    'uri' => 'https://www.youtube.com/watch?v=Upjg7F28DJw',
                    'title' => 'The Nuke-Proof US Military Base in a Mountain',
                    'content' => '',
                    'timestamp' => 1737788119,
                    'author' => 'Half as Interesting',
                    'id' => 'yt:video:Upjg7F28DJw',
                    'published' => '2025-01-24T15:44:18+00:00',
                    'updated' => '2025-01-25T06:55:19+00:00',
                    'link' => '',
                    'yt' => [
                        'videoId' => 'Upjg7F28DJw',
                        'channelId' => 'UCuCkxoKLYO_EQ2GeFtbM_bw',
                    ],
                    'media' => [
                        'group' => [
                            'title' => 'The Nuke-Proof US Military Base in a Mountain',
                            'content' => '',
                            'thumbnail' => '',
                            'description' => 'Receive 10% off anything on bellroy.com: https://bit.ly/3HdOWu9',
                            'community' => [
                                'starRating' => '',
                                'statistics' => '',
                            ],
                        ],
                    ],
                ]
            ],
        ];
        $this->assertEquals($expected, $feed);
    }
}
