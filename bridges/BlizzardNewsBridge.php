<?php

class BlizzardNewsBridge extends XPathAbstract
{
    const NAME = 'Blizzard News';
    const URI = 'https://news.blizzard.com';
    const DESCRIPTION = 'Blizzard (game company) newsfeed';
    const MAINTAINER = 'Niehztog';
    const PARAMETERS = [
        '' => [
            'locale' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'Deutsch' => 'de-de',
                    'English (EU)' => 'en-gb',
                    'English (US)' => 'en-us',
                    'Español (EU)' => 'es-es',
                    'Español (AL)' => 'es-mx',
                    'Français' => 'fr-fr',
                    'Italiano' => 'it-it',
                    '日本語' => 'ja-jp',
                    '한국어' => 'ko-kr',
                    'Polski' => 'pl-pl',
                    'Português (AL)' => 'pt-br',
                    'Русский' => 'ru-ru',
                    'ภาษาไทย' => 'th-th',
                    '简体中文' => 'zh-cn',
                    '繁體中文' => 'zh-tw'
                ],
                'defaultValue' => 'en-us',
                'title' => 'Select your language'
            ]
        ]
    ];
    const CACHE_TIMEOUT = 3600;

    const XPATH_EXPRESSION_ITEM = '/html/body/div/div[4]/div[2]/div[2]/div/div/section/ol/li/article';
    const XPATH_EXPRESSION_ITEM_TITLE = './/div/div[2]/h2';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/div[@class="ArticleListItem-description"]/div[@class="h6"]';
    const XPATH_EXPRESSION_ITEM_URI = './/a[@class="ArticleLink ArticleLink"]/@href';
    const XPATH_EXPRESSION_ITEM_AUTHOR = '';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/time[@class="ArticleListItem-footerTimestamp"]/@timestamp';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/div[@class="ArticleListItem-image"]/@style';
    const XPATH_EXPRESSION_ITEM_CATEGORIES = './/div[@class="ArticleListItem-label"]';
    const SETTING_FIX_ENCODING = true;

    /**
     * Source Web page URL (should provide either HTML or XML content)
     * @return string
     */
    protected function getSourceUrl()
    {
        $locale = $this->getInput('locale');
        if ('zh-cn' === $locale) {
            return 'https://cn.news.blizzard.com';
        }
        return 'https://news.blizzard.com/' . $locale;
    }
}
