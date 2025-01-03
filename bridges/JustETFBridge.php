<?php

class JustETFBridge extends BridgeAbstract
{
    const NAME = 'justETF Bridge';
    const URI = 'https://www.justetf.com';
    const DESCRIPTION = 'Currently only supports the news feed';
    const MAINTAINER = 'logmanoriginal';
    const PARAMETERS = [
        'News' => [
            'full' => [
                'name' => 'Full Article',
                'type' => 'checkbox',
                'title' => 'Enable to load full articles'
            ]
        ],
        'Profile' => [
            'isin' => [
                'name' => 'ISIN',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'IE00B4X9L533',
                'pattern' => '[a-zA-Z]{2}[a-zA-Z0-9]{10}',
                'title' => 'ISIN, consisting of 2-letter country code, 9-character identifier, check character'
            ],
            'strategy' => [
                'name' => 'Include Strategy',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],
            'description' => [
                'name' => 'Include Description',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ]
        ],
        'global' => [
            'lang' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'Englisch' => 'en',
                    'Deutsch'  => 'de',
                    'Italiano' => 'it'
                ],
                'defaultValue' => 'Englisch'
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        defaultLinkTo($html, static::URI);

        switch ($this->queriedContext) {
            case 'News':
                $this->collectNews($html);
                break;
            case 'Profile':
                $this->collectProfile($html);
                break;
        }
    }

    public function getURI()
    {
        $uri = static::URI;

        if ($this->getInput('lang')) {
            $uri .= '/' . $this->getInput('lang');
        }

        switch ($this->queriedContext) {
            case 'News':
                $uri .= '/news';
                break;
            case 'Profile':
                $uri .= '/etf-profile.html?' . http_build_query([
                    'isin' => strtoupper($this->getInput('isin'))
                ]);
                break;
        }

        return $uri;
    }

    public function getName()
    {
        $name = static::NAME;

        $name .= ($this->queriedContext) ? ' - ' . $this->queriedContext : '';

        switch ($this->queriedContext) {
            case 'News':
                break;
            case 'Profile':
                if ($this->getInput('isin')) {
                    $name .= ' ISIN ' . strtoupper($this->getInput('isin'));
                }
        }

        if ($this->getInput('lang')) {
            $name .= ' (' . strtoupper($this->getInput('lang')) . ')';
        }

        return $name;
    }

    #region Common

    /**
     * Fixes dates depending on the choosen language:
     *
     * de : dd.mm.yy
     * en : dd.mm.yy
     * it : dd/mm/yy
     *
     * Basically strtotime doesn't convert dates correctly due to formats
     * being hard to interpret. So we use the DateTime object, manually
     * fixing dates and times (set to 00:00:00.000).
     *
     * We don't know the timezone, so just assume +00:00 (or whatever
     * DateTime chooses)
     */
    private function fixDate($date)
    {
        switch ($this->getInput('lang')) {
            case 'en':
            case 'de':
                $df = date_create_from_format('d.m.y', $date);
                break;
            case 'it':
                $df = date_create_from_format('d/m/y', $date);
                break;
        }

        date_time_set($df, 0, 0);

        return date_format($df, 'U');
    }

    private function extractImages($article)
    {
        // Notice: We can have zero or more images (though it should mostly be 1)
        $elements = $article->find('img');

        $images = [];

        foreach ($elements as $img) {
            // Skip the logo (mostly provided part of a hidden div)
            if (substr($img->src, strrpos($img->src, '/') + 1) === 'logo.png') {
                continue;
            }

            $images[] = $img->src;
        }

        return $images;
    }

    #endregion

    #region News

    private function collectNews($html)
    {
        $articles = $html->find('div.newsTopArticle')
            or returnServerError('No articles found! Layout might have changed!');

        foreach ($articles as $article) {
            $item = [];

            // Common data

            $item['uri'] = $this->extractNewsUri($article);
            $item['timestamp'] = $this->extractNewsDate($article);
            $item['title'] = $this->extractNewsTitle($article);

            if ($this->getInput('full')) {
                $uri = $this->extractNewsUri($article);

                $html = getSimpleHTMLDOMCached($uri);

                $fullArticle = $html->find('div.article', 0)
                    or returnServerError('No content found! Layout might have changed!');

                defaultLinkTo($fullArticle, static::URI);

                $item['author'] = $this->extractFullArticleAuthor($fullArticle);
                $item['content'] = $this->extractFullArticleContent($fullArticle);
                $item['enclosures'] = $this->extractImages($fullArticle);
            } else {
                $item['content'] = $this->extractNewsDescription($article);
                $item['enclosures'] = $this->extractImages($article);
            }

            $this->items[] = $item;
        }
    }

    private function extractNewsUri($article)
    {
        $element = $article->find('a', 0)
            or returnServerError('Anchor not found!');

        return $element->href;
    }

    private function extractNewsDate($article)
    {
        $element = $article->find('div.subheadline', 0)
            or returnServerError('Date not found!');

        $date = trim(explode('|', $element->plaintext)[0]);

        return $this->fixDate($date);
    }

    private function extractNewsDescription($article)
    {
        $element = $article->find('span.newsText', 0)
            or returnServerError('Description not found!');

        $element->find('a', 0)->onclick = '';

        return $element->innertext;
    }

    private function extractNewsTitle($article)
    {
        $element = $article->find('h3', 0)
            or returnServerError('Title not found!');

        return $element->plaintext;
    }

    private function extractFullArticleContent($article)
    {
        $element = $article->find('div.article_body', 0)
            or returnServerError('Article body not found!');

        // Remove teaser image
        $element->find('img.teaser-img', 0)->outertext = '';

        // Remove self advertisements
        foreach ($element->find('.call-action') as $adv) {
            $adv->outertext = '';
        }

        // Remove tips
        foreach ($element->find('.panel-edu') as $tip) {
            $tip->outertext = '';
        }

        // Remove inline scripts (used for i.e. interactive graphs) as they are
        // rendered as a long series of strings
        foreach ($element->find('script') as $script) {
            $script->outertext = '[Content removed! Visit site to see full contents!]';
        }

        return $element->innertext;
    }

    private function extractFullArticleAuthor($article)
    {
        $element = $article->find('span[itemprop=name]', 0)
            or returnServerError('Author not found!');

        return $element->plaintext;
    }

    #endregion

    #region Profile

    private function collectProfile($html)
    {
        $item = [];

        $item['uri'] = $this->getURI();
        $item['timestamp'] = $this->extractProfileDate($html);
        $item['title'] = $this->extractProfiletitle($html);
        $item['author'] = $this->extractProfileAuthor($html);
        $item['content'] = $this->extractProfileContent($html);

        $this->items[] = $item;
    }

    private function extractProfileDate($html)
    {
        $element = $html->find('div.infobox div.vallabel', 0)
            or returnServerError('Date not found!');

        $date = trim(explode("\r\n", $element->plaintext)[1]);

        return $this->fixDate($date);
    }

    private function extractProfileTitle($html)
    {
        $element = $html->find('span.h1', 0)
            or returnServerError('Title not found!');

        return $element->plaintext;
    }

    private function extractProfileContent($html)
    {
        // There are a few thins we are interested:
        // - Investment Strategy
        // - Description
        // - Quote

        $strategy = $html->find('div.tab-container div.col-sm-6 p', 0)
            or returnServerError('Investment Strategy not found!');

        // Description requires a bit of cleanup due to lack of propper identification

        $description = $html->find('div.headline', 5)
            or returnServerError('Description container not found!');

        $description = $description->parent();

        foreach ($description->find('div') as $div) {
            $div->outertext = '';
        }

        $quote = $html->find('div.infobox div.val', 0)
            or returnServerError('Quote not found!');

        $quote_html = '<strong>Quote</strong><br><p>' . $quote . '</p>';
        $strategy_html = '';
        $description_html = '';

        if ($this->getInput('strategy') === true) {
            $strategy_html = '<strong>Strategy</strong><br><p>' . $strategy . '</p><br>';
        }

        if ($this->getInput('description') === true) {
            $description_html = '<strong>Description</strong><br><p>' . $description . '</p><br>';
        }

        return $strategy_html . $description_html . $quote_html;
    }

    private function extractProfileAuthor($html)
    {
        // Use ISIN + WKN as author
        // Notice: "identfier" is not a typo [sic]!
        $element = $html->find('span.identfier', 0)
            or returnServerError('Author not found!');

        return $element->plaintext;
    }

    #endregion
}
