<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// phpcs:ignoreFile

class YoutubeTest extends TestCase
{
    public function testHandleYoutubeIframe()
    {
        $config = [
            'youtube' => [
                'iframe' => true,
                'nocookie' => false,
            ]
        ];
        Configuration::loadConfiguration($config);

        $expected = <<<'HTML'
            <iframe
                width="560"
                height="315"
                src="https://www.youtube.com/embed/GLrfqtf4txw"
                title="YouTube video player"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin"
                allowfullscreen
            >
            </iframe>

            HTML;

        $this->assertSame($expected, handleYoutube('https://www.youtube.com/watch?v=GLrfqtf4txw'));
    }

    public function testHandleYoutubeNotIframe()
    {
        $config = [
            'youtube' => [
                'iframe' => false,
                'nocookie' => false,
            ]
        ];
        Configuration::loadConfiguration($config);

        $expected = <<<'HTML'
            <a href="https://www.youtube.com/watch?v=GLrfqtf4txw">
                <picture>
                    <source
                        srcset="https://i.ytimg.com/vi_webp/GLrfqtf4txw/mqdefault.webp 320w, https://i.ytimg.com/vi_webp/GLrfqtf4txw/0.webp 480w, https://i.ytimg.com/vi_webp/GLrfqtf4txw/hqdefault.webp 481w, https://i.ytimg.com/vi_webp/GLrfqtf4txw/sddefault.webp 640w, https://i.ytimg.com/vi_webp/GLrfqtf4txw/hq720.webp 720w, https://i.ytimg.com/vi_webp/GLrfqtf4txw/maxresdefault.webp 721w"
                        type="image/webp"
                        referrerpolicy="no-referrer"
                    />
                    <img
                        srcset="https://i.ytimg.com/vi/GLrfqtf4txw/mqdefault.jpg 320w, https://i.ytimg.com/vi/GLrfqtf4txw/0.jpg 480w, https://i.ytimg.com/vi/GLrfqtf4txw/hqdefault.jpg 481w, https://i.ytimg.com/vi/GLrfqtf4txw/sddefault.jpg 640w, https://i.ytimg.com/vi/GLrfqtf4txw/hq720.jpg 720w, https://i.ytimg.com/vi/GLrfqtf4txw/maxresdefault.jpg 721w"
                        src="https://i.ytimg.com/vi/GLrfqtf4txw/maxresdefault.jpg"
                        alt="Video thumbnail"
                        title="YouTube video thumbnail"
                        referrerpolicy="no-referrer"
                    />
                </picture>
            </a>
            <p>
                <a href="https://www.youtube.com/watch?v=GLrfqtf4txw">https://www.youtube.com/watch?v=GLrfqtf4txw</a>
            </p>

            HTML;

        $this->assertSame($expected, handleYoutube('https://www.youtube.com/watch?v=GLrfqtf4txw'));
    }
}
