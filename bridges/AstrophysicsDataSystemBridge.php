<?php

class AstrophysicsDataSystemBridge extends BridgeAbstract
{
    const NAME = 'SAO/NASA Astrophysics Data System';
    const DESCRIPTION = 'Returns the latest publications from a query';
    const URI = 'https://ui.adsabs.harvard.edu';
    const PARAMETERS = [
        'Publications' => [
            'query' => [
                'name' => 'query',
                'title' => 'Same format as the search bar on the website',
                'exampleValue' => 'author:"huchra, john"',
                'required' => true
            ]
        ]];

    private $feedTitle;

    public function getName()
    {
        if ($this->queriedContext === 'Publications') {
            return $this->feedTitle;
        }
        return parent::getName();
    }

    public function getURI()
    {
        if ($this->queriedContext === 'Publications') {
            return self::URI . '/search/?q=' . urlencode($this->getInput('query'));
        }
        return parent::getURI();
    }

    public function collectData()
    {
        $headers = [
            'Cookie: core=always;'
        ];
        $html = str_get_html(defaultLinkTo(getContents($this->getURI(), $headers), self::URI));
        $this->feedTitle = html_entity_decode($html->find('title', 0)->plaintext);
        foreach ($html->find('div.row > ul > li') as $pub) {
            $item = [];
            $item['title'] = $pub->find('h3.s-results-title', 0)->plaintext;
            $item['content'] = $pub->find('div.s-results-links', 0);
            $item['uri'] = $pub->find('a.abs-redirect-link', 0)->href;
            $item['author'] = rtrim($pub->find('li.article-author', 0)->plaintext, ' ;');
            $item['timestamp'] = $pub->find('div[aria-label="date published"]', 0)->plaintext;
            $this->items[] = $item;
        }
    }
}
