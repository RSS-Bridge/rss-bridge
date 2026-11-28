<?php

declare(strict_types=1);

namespace RssBridge\Tests;

use PHPUnit\Framework\TestCase;

class TelegramBridgeTest extends TestCase
{
    private const NOT_SUPPORTED = <<<'HTML'
        <div class="media_not_supported_cont"><div class="message_media_not_supported_wrap">
          <div class="message_media_not_supported">
            <div class="message_media_not_supported_label">Please open Telegram to view this post</div>
            <a href="https://t.me/rssbridge/1" class="message_media_view_in_telegram">VIEW IN TELEGRAM</a>
          </div>
        </div></div>
        HTML;

    protected function setUp(): void
    {
        \Configuration::loadConfiguration();
    }

    public function testFallbackTextIsOmittedWhenThePostHasContent()
    {
        $html = '<div class="tgme_widget_message"><div class="tgme_widget_message_text js-message_text">Hello world</div>'
            . self::NOT_SUPPORTED
            . '</div>';

        $content = $this->processContent($html);

        $this->assertStringContainsString('Hello world', $content);
        $this->assertStringNotContainsString('Please open Telegram to view this post', $content);
        $this->assertStringNotContainsString('VIEW IN TELEGRAM', $content);
    }

    public function testFallbackTextIsKeptWhenThePostHasNoOtherContent()
    {
        $html = '<div class="tgme_widget_message">' . self::NOT_SUPPORTED . '</div>';

        $content = $this->processContent($html);

        $this->assertStringContainsString('Please open Telegram to view this post', $content);
    }

    public function testFallbackTextIsKeptForAReplyWithNoOtherContent()
    {
        $reply = '<a class="tgme_widget_message_reply"><span class="tgme_widget_message_author_name">RSS-Bridge</span></a>';
        $html = '<div class="tgme_widget_message">' . $reply . self::NOT_SUPPORTED . '</div>';

        $content = $this->processContent($html);

        $this->assertStringContainsString('<blockquote>RSS-Bridge', $content);
        $this->assertStringContainsString('Please open Telegram to view this post', $content);
    }

    private function processContent(string $html): string
    {
        $bridge = new \TelegramBridge(new \ArrayCache(), new \NullLogger());
        $method = new \ReflectionMethod($bridge, 'processContent');
        $method->setAccessible(true);

        return $method->invoke($bridge, str_get_html($html)->find('div.tgme_widget_message', 0));
    }
}
