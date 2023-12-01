<?php

class FallGuysBridge extends BridgeAbstract
{
    const MAINTAINER = 'User123698745';
    const NAME = 'Fall Guys';
    const BASE_URI = 'https://www.fallguys.com';
    const URI = self::BASE_URI . '/news';
    const CACHE_TIMEOUT = 600; // 10min
    const DESCRIPTION = 'News from the Fall Guys website';
    const DEFAULT_LOCALE = 'en-US';
    const PARAMETERS = [
        [
            'locale' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'English' => 'en-US',
                    'لعربية' => 'ar',
                    'Deutsch' => 'de',
                    'Español (Spain)' => 'es-ES',
                    'Español (LA)' => 'es-MX',
                    'Français' => 'fr',
                    'Italiano' => 'it',
                    '日本語' => 'ja',
                    '한국어' => 'ko',
                    'Polski' => 'pl',
                    'Português (Brasil)' => 'pt-BR',
                    'Русский' => 'ru',
                    'Türkçe' => 'tr',
                    '简体中文' => 'zh-CN',
                ],
                'defaultValue' => self::DEFAULT_LOCALE,
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::getURI());

        $data = json_decode($html->find('#__NEXT_DATA__', 0)->innertext);

        foreach ($data->props->pageProps->newsList as $newsItem) {
            $headerDescription = property_exists($newsItem->header, 'description') ? $newsItem->header->description : '';
            $headerImage = $newsItem->header->image->src;

            $contentImages = [$headerImage];

            $content = <<<HTML
            <p>{$headerDescription}</p>
            <p><img src="{$headerImage}"></p>
            HTML;

            foreach ($newsItem->content->items as $contentItem) {
                if (property_exists($contentItem, 'articleCopy')) {
                    if (property_exists($contentItem->articleCopy, 'title')) {
                        $title = $contentItem->articleCopy->title;

                        $content .= <<<HTML
                        <h2>{$title}</h2>
                        HTML;
                    }

                    $text = $contentItem->articleCopy->copy;

                    $content .= <<<HTML
                    <p>{$text}</p>
                    HTML;
                } elseif (property_exists($contentItem, 'articleImage')) {
                    $image = $contentItem->articleImage->imageSrc;

                    if ($image != $headerImage) {
                        $contentImages[] = $image;

                        $content .= <<<HTML
                        <p><img src="{$image}"></p>
                        HTML;
                    }
                } elseif (property_exists($contentItem, 'embeddedVideo')) {
                    $mediaOptions = $contentItem->embeddedVideo->mediaOptions;
                    $mainContentOptions = $contentItem->embeddedVideo->mainContentOptions;

                    if (count($mediaOptions) == count($mainContentOptions)) {
                        for ($i = 0; $i < count($mediaOptions); $i++) {
                            if (property_exists($mediaOptions[$i], 'youtubeVideo')) {
                                $videoUrl = 'https://youtu.be/' . $mediaOptions[$i]->youtubeVideo->contentId;
                                $image = $mainContentOptions[$i]->image->src ?? '';

                                $content .= '<p>';

                                if ($image != $headerImage) {
                                    $contentImages[] = $image;

                                    $content .= <<<HTML
                                    <a href="{$videoUrl}"><img src="{$image}"></a><br>
                                    HTML;
                                }

                                $content .= <<<HTML
                                <i>(Video: <a href="{$videoUrl}">{$videoUrl}</a>)</i>
                                HTML;

                                $content .= '</p>';
                            }
                        }
                    }
                }
            }

            $item = [
                'uid' => $newsItem->_id,
                'uri' => self::getURI() . '/' . $newsItem->_slug,
                'title' => $newsItem->_title,
                'timestamp' => $newsItem->lastModified,
                'content' => $content,
                'enclosures' => $contentImages,
            ];

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        $locale = $this->getInput('locale') ?? self::DEFAULT_LOCALE;
        return self::BASE_URI . '/' . $locale . '/news';
    }

    public function getIcon()
    {
        return self::BASE_URI . '/favicon.ico';
    }
}
