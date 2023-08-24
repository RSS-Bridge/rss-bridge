<?php

class ScientificAmericanBridge extends FeedExpander
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Scientific American';
    const URI = 'https://www.scientificamerican.com/';

    const CACHE_TIMEOUT = 60 * 60 * 1; // 1 hour
    const DESCRIPTION = 'All articles from the latest feed, plus articles in issues.';

    const PARAMETERS = [
        '' => [
            'parseIssues' => [
                'name' => 'Number of issues to parse and add to the feed. Takes longer to load, but includes all articles.',
                'type' => 'number',
                'defaultValue' => 0,
            ],
            'addContents' => [
                'name' => 'Also fetch contents for articles',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ]
        ]
    ];

    const FEED = 'http://rss.sciam.com/ScientificAmerican-Global';
    const ISSUES = 'https://www.scientificamerican.com/archive/issues/2020s/';

    public function collectData()
    {
        $this->collectIssues();
        $items = [
            ...$this->collectFeed(),
            ...$this->collectIssues()
        ];

        $saved = [];

        foreach ($items as $item) {
            if (!array_key_exists($item['uri'], $saved)) {
                $saved[$item['uri']] = 1;
                if ($this->getInput('addContents') == 1) {
                    $this->items[] = $this->updateItem($item);
                } else {
                    $this->items[] = $item;
                }
            }
        }

        if ($this->getInput('addContents') == 1) {
            usort($this->items, function ($item1, $item2) {
                return $item1['timestamp'] - $item2['timestamp'];
            });
        }
    }

    private function collectFeed()
    {
        $this->collectExpandableDatas(self::FEED);
        $items = $this->items;
        $this->items = [];
        return $items;
    }

    private function collectIssues()
    {
        $html = getSimpleHTMLDOMCached(self::ISSUES);
        $content = $html->getElementById('content')->children(3);
        $issues = $content->children();
        $issues_count = min(
            (int)$this->getInput('parseIssues'),
            count($issues)
        );

        $items = [];
        for ($i = 0; $i < $issues_count; $i++) {
            $a = $issues[$i]->find('a', 0);
            $link = 'https://scientificamerican.com' . $a->getAttribute('href');
            array_push($items, ...$this->parseIssue($link));
        }
        return $items;
    }

    private function parseIssue($issue_link)
    {
        $items = [];
        $html = getSimpleHTMLDOMCached($issue_link);

        $features = $html->find('[class^=Detail_issue__article__previews__featured]', 0);
        if ($features != null) {
            $articles = $features->find('div', 0)->children();
            foreach ($articles as $article) {
                $h4 = $article->find('h4', 0);
                $a = $h4->find('a', 0);
                $link = 'https://scientificamerican.com' . $a->getAttribute('href');
                $title = $a->plaintext;
                $items[] = [
                    'uri' => $link,
                    'title' => $title,
                    'uid' => $link,
                    'content' => ''
                ];
            }
        }

        $departments = $html->find('[class^=Detail_issue__article__previews__departments]', 0);
        if ($departments != null) {
            $headers = $departments->find('[class*=Listing_article__listing__title]');
            foreach ($headers as $header) {
                $a = $header->find('a', 0);
                $link = 'https://scientificamerican.com' . $a->getAttribute('href');
                $title = $a->plaintext;
                $items[] = [
                    'uri' => $link,
                    'title' => $title,
                    'uid' => $link,
                    'content' => ''
                ];
            }
        }

        return $items;
    }

    private function updateItem($item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
        $article = $html->find('#sa_body', 0)->find('article', 0);

        $time = $article->find('time[itemprop="datePublished"]', 0);
        if ($time == null) {
            $time = $html->find('span[itemprop="datePublished"]', 0);
        }
        if ($time) {
            $datetime = DateTime::createFromFormat('F j, Y', $time->plaintext);
            $item['timestamp'] = $datetime->format('U');
        }
        $main = $article->find('section.article-grid__main', 0);

        if ($main == null) {
            $main = $article->find('div.article-text', 0);
        }

        if ($main == null) {
            return $item;
        }

        foreach ($main->find('img') as $img) {
            $img->removeAttribute('width');
            $img->removeAttribute('height');
            $img->setAttribute('style', 'height: auto; width: auto; max-height: 768px');
        }

        $rights_link = $main->find('div.article-rightslink', 0);
        if ($rights_link != null) {
            $rights_link->parent->removeChild($rights_link);
        }
        $reprints_link = $main->find('div.article-reprintsLink', 0);
        if ($reprints_link != null) {
            $reprints_link->parent->removeChild($reprints_link);
        }
        $about_section = $main->find('section.article-author-container', 0);
        if ($about_section != null) {
            $about_section->parent->removeChild($about_section);
        }
        $read_next = $main->find('#read-next', 0);
        if ($read_next != null) {
            $read_next->parent->removeChild($read_next);
        }

        foreach ($main->find('iframe') as $iframe) {
            $a = $html->createElement('a');
            $a->href = $iframe->src;
            $a->innertext = $iframe->src;
            $iframe->parent->appendChild($a);
            $iframe->parent->removeChild($iframe);
        }

        $authors = $main->find('span[itemprop="author"]', 0);
        if ($authors != null) {
            $item['author'] = $authors->plaintext;
        }

        $item['content'] = $main->innertext;
        return $item;
    }
}
