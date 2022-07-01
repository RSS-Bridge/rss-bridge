<?php

class EsquerdaNetBridge extends FeedExpander
{
    const MAINTAINER = 'somini';
    const NAME = 'Esquerda.net';
    const URI = 'https://www.esquerda.net';
    const DESCRIPTION = 'Esquerda.net';
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'Feed',
                'type' => 'list',
                'defaultValue' => 'Geral',
                'values' => [
                    'Geral' => 'geral',
                    'Dossier' => 'artigos-dossier',
                    'Vídeo' => 'video',
                    'Opinião' => 'opinioes',
                    'Rádio' => 'radio',
                ]
            ]
        ]
    ];

    public function getURI()
    {
        $type = $this->getInput('feed');
        return self::URI . '/rss/' . $type;
    }

    public function getIcon()
    {
        return 'https://www.esquerda.net/sites/default/files/favicon_0.ico';
    }

    public function collectData()
    {
        parent::collectExpandableDatas($this->getURI());
    }

    protected function parseItem($newsItem)
    {
        # Fix Publish date
        $badDate = $newsItem->pubDate;
        preg_match('|(?P<day>\d\d)/(?P<month>\d\d)/(?P<year>\d\d\d\d) - (?P<hour>\d\d):(?P<minute>\d\d)|', $badDate, $d);
        $newsItem->pubDate = sprintf('%s-%s-%sT%s:%s', $d['year'], $d['month'], $d['day'], $d['hour'], $d['minute']);
        $item = parent::parseItem($newsItem);
        # Include all the content
        $uri = $item['uri'];
        $html = getSimpleHTMLDOMCached($uri);
        $content = $html->find('div#content div.content', 0);
        ## Fix author
        $authorHTML = $html->find('.field-name-field-op-author a', 0);
        if ($authorHTML) {
            $item['author'] = $authorHTML->innertext;
            $authorHTML->remove();
        }
        ## Remove crap
        $content->find('.field-name-addtoany', 0)->remove();
        ## Fix links
        $content = defaultLinkTo($content, self::URI);
        ## Fix Images
        foreach ($content->find('img') as $img) {
            $altSrc = $img->getAttribute('data-src');
            if ($altSrc) {
                $img->setAttribute('src', $altSrc);
            }
            $img->width = null;
            $img->height = null;
        }
        $item['content'] = $content;
        return $item;
    }
}
