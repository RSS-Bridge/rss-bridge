<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UberEngineeringBridgeTest extends TestCase
{
    public function testExtractArticleFeedFromHtml(): void
    {
        $payload = rawurlencode((string) json_encode([
            'relatedPages' => [
                'relatedPages' => [[
                    'fullURL' => 'www.uber.com/us/en/blog/scaling-responsible-ai/',
                    'ogImageURL' => 'https://tb-static.uber.com/prod/udam-assets/example.png',
                    'publishedAt' => '2026-04-09T20:42:48.601Z',
                    'title' => 'Under the Hood: Scaling Responsible AI at Uber',
                ]],
                'totalCount' => 1,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $html = sprintf(
            '<html><body><script type="application/json" id="%s">%s</script></body></html>',
            '__LOCAL_REDUX_STATE_Newsroom_Article Feed Store_%2Fus%2Fen%2Fblog%2Fengineering%2F__',
            $payload
        );

        $actual = UberEngineeringBridge::extractArticleFeedFromHtml($html, '/us/en/blog/engineering/');

        $this->assertCount(1, $actual);
        $this->assertSame('Under the Hood: Scaling Responsible AI at Uber', $actual[0]['title']);
        $this->assertSame(
            'https://www.uber.com/us/en/blog/scaling-responsible-ai/',
            UberEngineeringBridge::normalizeUrl($actual[0]['fullURL'])
        );
    }

    public function testExtractArticleFeedFromHtmlThrowsWhenFeedScriptIsMissing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to find article feed data');

        UberEngineeringBridge::extractArticleFeedFromHtml('<html></html>', '/us/en/blog/engineering/');
    }
}
