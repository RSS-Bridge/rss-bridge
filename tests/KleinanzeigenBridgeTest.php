<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class KleinanzeigenBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::loadConfiguration();
    }

    private function parseSearchPage(int $capturedPage, int $requestedPage): array
    {
        $html = file_get_contents(__DIR__ . '/fixtures/kleinanzeigen/search-page-' . $capturedPage . '.html');
        $bridge = new KleinanzeigenBridge(new NullCache(), new NullLogger());
        $method = new ReflectionMethod(KleinanzeigenBridge::class, 'collectSearchPage');
        $method->setAccessible(true);
        $result = $method->invoke($bridge, str_get_html($html), $requestedPage);
        return [$result, $bridge->getItems()];
    }

    public function testCapturedSearchPage(): void
    {
        [$result, $items] = $this->parseSearchPage(1, 1);

        $this->assertTrue($result);
        $this->assertCount(25, $items);
        $item = $items[0];
        $this->assertSame('Apple iMac 27" Late 2013 i5 3,4GHz 16GB 256GB SSD GTX 780M 4GB', $item['title']);
        $this->assertSame('3534541670', $item['uid']);
        $this->assertSame(
            'https://www.kleinanzeigen.de/s-anzeige/apple-imac-27-late-2013-i5-3-4ghz-16gb-256gb-ssd-gtx-780m-4gb/3534541670-228-6233',
            $item['uri']
        );
        $this->assertSame(strtotime('today, 12:22'), $item['timestamp']);
        $this->assertSame([
            'https://img.kleinanzeigen.de/api/v1/prod-ads/images/c6/c6ccfd36-60e9-4e6d-94b5-3b0bc1081df5?rule=$_57.AUTO#.image',
        ], $item['enclosures']);
        $content = (string)$item['content'];
        $this->assertStringContainsString('Die interne SSD wurde gelöscht und macOS Catalina frisch installiert.', $content);
        $this->assertStringContainsString('93336 Altmannstein', $content);
        $this->assertStringContainsString('199 € VB', $content);
        $this->assertStringContainsString('Direkt kaufen', $content);
        $this->assertStringContainsString('Versand möglich', $content);
        $this->assertStringNotContainsString('<svg', $content);
        $this->assertStringNotContainsString('<script', $content);
    }

    public function testCapturedSecondPage(): void
    {
        [$result, $items] = $this->parseSearchPage(2, 2);
        $this->assertTrue($result);
        $this->assertCount(25, $items);
        $this->assertSame('3528739779', $items[0]['uid']);
        $this->assertSame('Apple iMac 27" – Late 2013 | 8 GB RAM | 119 €', $items[0]['title']);
        $this->assertSame(strtotime('2026-10-02'), $items[0]['timestamp']);

        [, $firstPageItems] = $this->parseSearchPage(1, 1);
        $ids = array_column(array_merge($firstPageItems, $items), 'uid');
        $this->assertCount(50, array_unique($ids));
    }

    public function testCapturedPreviousPrice(): void
    {
        [, $items] = $this->parseSearchPage(1, 1);
        $itemsById = array_column($items, null, 'uid');
        $content = (string)$itemsById['3534283314']['content'];
        $this->assertStringContainsString('60 € VB', $content);
        $this->assertStringContainsString('<s><p>75 €</p></s>', $content);
    }

    public function testRedirectToCapturedEarlierPageDoesNotDuplicateListings(): void
    {
        [$result, $items] = $this->parseSearchPage(1, 2);
        $this->assertFalse($result);
        $this->assertSame([], $items);
    }
}
