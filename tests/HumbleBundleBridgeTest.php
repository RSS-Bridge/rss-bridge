<?php

use PHPUnit\Framework\TestCase;

class HumbleBundleBridgeTest extends TestCase
{
    private function page(string $stream): string
    {
        $html = '';
        foreach (str_split($stream, 55) as $chunk) {
            $html .= '<script>self.__next_f.push(' . json_encode([1, $chunk]) . ')</script>';
        }
        return $html;
    }

    private function bridge(array $pages): HumbleBundleBridge
    {
        $bridge = $this->getMockBuilder(HumbleBundleBridge::class)
            ->setConstructorArgs([new ArrayCache(), new NullLogger()])
            ->onlyMethods(['fetchPage'])
            ->getMock();
        $bridge->method('fetchPage')->willReturnCallback(function ($uri) use ($pages) {
            $path = parse_url($uri, PHP_URL_PATH);
            if (!isset($pages[$path])) {
                throw new HttpException('Bundle detail unavailable', 404);
            }
            return $pages[$path];
        });
        $bridge->setInput(['type' => 'bundles']);
        return $bridge;
    }

    private function listing(array $entries): string
    {
        $section = ['surface' => 'bundles-listing', 'feedItems' => $entries];
        return $this->page(":HL[]\n50:" . json_encode(['$', 'component', null, $section]) . "\n");
    }

    public function testBundleTiersAndUnavailableDetail(): void
    {
        $entry = ['value' => ['productKey' => 'game_bundle', 'url' => '/games/game', 'title' => 'Game']];
        $book = ['value' => ['url' => '/books/book', 'title' => 'Book', 'includedItemTitles' => ['One book']]];
        $item = ['key' => 'first', 'title' => 'First', 'description' => '$a'];
        $tiers = [
            ['price' => ['amount' => 5, 'currencyCode' => 'USD'], 'bundleItems' => [$item]],
            ['bundleItems' => [$item, '$e:props:bundlePage:tiers:0:bundleItems:0', ['title' => 'Second']]],
        ];
        $description = "<em>First</em>\n café & more";
        $textRow = 'a:T' . dechex(strlen($description)) . ',' . $description;
        $bundle = ['bundlePage' => ['tiers' => $tiers, 'startDate' => '2026-01-01T00:00:00Z']];
        $bridge = $this->bridge([
            '/bundles' => $this->listing([$entry, $book, $entry]),
            '/games/game' => $this->page($textRow . 'e:' . json_encode($bundle) . "\n"),
        ]);
        $bridge->collectData();
        [$game, $fallback] = $bridge->getItems();
        $this->assertCount(2, $bridge->getItems());
        $this->assertSame('game_bundle', $game['uid']);
        $this->assertSame('2026-01-01T00:00:00Z', $game['timestamp']);
        $this->assertSame(1, substr_count($game['content'], '<h3>First</h3>'));
        $this->assertStringContainsString("First\n café &amp; more", $game['content']);
        $this->assertStringContainsString('<h2>5 USD</h2>', $game['content']);
        $this->assertStringContainsString('<h2>Tier 2</h2><h3>Second</h3>', $game['content']);
        $this->assertSame('https://www.humblebundle.com/books/book', $fallback['uri']);
        $this->assertStringContainsString('One book', $fallback['content']);
    }

    public function testSharedItemAppearsAtLowestPrice(): void
    {
        $tiers = [
            ['price' => ['amount' => 10, 'currencyCode' => 'USD'], 'bundleItems' => [
                ['title' => 'Shared', 'description' => 'Full description'],
                ['title' => 'Premium'],
            ]],
            ['price' => ['amount' => 5, 'currencyCode' => 'USD'], 'bundleItems' => [
                '$e:props:bundlePage:tiers:0:bundleItems:0',
            ]],
        ];
        $bridge = $this->bridge([
            '/bundles' => $this->listing([['value' => ['url' => '/games/game', 'title' => 'Game']]]),
            '/games/game' => $this->page('e:' . json_encode(['bundlePage' => ['tiers' => $tiers]]) . "\n"),
        ]);
        $bridge->collectData();
        $this->assertSame(
            '<h2>5 USD</h2><h3>Shared</h3><p>Full description</p><h2>10 USD</h2><h3>Premium</h3>',
            $bridge->getItems()[0]['content']
        );
    }

    public function testEmptyCategory(): void
    {
        $bridge = $this->bridge(['/games' => $this->listing([])]);
        $bridge->setInput(['type' => 'games']);
        $bridge->collectData();
        $this->assertSame([], $bridge->getItems());
    }

    public function testUnrecognizedListing(): void
    {
        $bridge = $this->bridge(['/bundles' => '<html>Unknown listing</html>']);
        $this->expectExceptionMessage('Unable to find the Humble Bundle listing');
        $bridge->collectData();
    }
}
