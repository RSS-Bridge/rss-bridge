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
    const ISSUES = 'https://www.scientificamerican.com/archive/issues/';

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
                return $item2['timestamp'] - $item1['timestamp'];
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
        $content = $html->getElementById('app');
        $issues_list = $content->find('div[class^="issue__list"]', 0);
        if ($issues_list == null) {
            return [];
        }
        $issues = $issues_list->find('div[class^="list__item"]');
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

        $blocks = $html->find('[class^="issueArchiveArticleListCompact"]');
        foreach ($blocks as $block) {
            $articles = $block->find('article[class*="article"]');
            foreach ($articles as $article) {
                $a = $article->find('a[class^="articleLink"]', 0);
                $link = 'https://scientificamerican.com' . $a->getAttribute('href');
                $title = $a->find('h2[class^="articleTitle"]', 0);
                array_push($items, [
                    'uri' => $link,
                    'title' => $title->plaintext,
                    'uid' => $link,
                    'content' => ''
                ]);
            }
        }

        return $items;
    }

    private function updateItem($item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
        $article = $html->find('#app', 0)->find('article', 0);

        $time = $article->find('p[class^="article_pub_date"]', 0);
        if ($time) {
            $datetime = DateTime::createFromFormat('F j, Y', $time->plaintext);
            $datetime->setTime(0, 0, 0, 0);
            $item['timestamp'] = $datetime->format('U');
        }

        $authors = $article->find('a[class^="article_authors__link"]');
        if ($authors) {
            $author = implode('; ', array_map(fn($a) => $a->plaintext, $authors));
            $item['author'] = $author;
        }

        $res = '';
        $desc = $article->find('div[class^="article_dek"]', 0);
        if ($desc) {
            $res .= $desc->innertext;
        }

        $lead_figure = $article->find('figure[class^="lead_image"]', 0);
        if ($lead_figure) {
            $res .= $lead_figure->outertext;
        }

        $content = $article->find('div[class^="article__content"]', 0);
        if ($content) {
            foreach ($content->children() as $block) {
                if (str_contains($block->innertext, 'On supporting science journalism')) {
                    continue;
                }
                if (
                    ($block->tag == 'p' && $block->getAttribute('data-block') == 'sciam/paragraph')
                    || ($block->tag == 'figure' && str_starts_with($block->class, 'article__image'))
                ) {
                    $iframe = $block->find('iframe', 0);
                    if ($iframe) {
                        $res .= "<a href=\"{$iframe->src}\">{$iframe->src}</a>";
                    } else {
                        $res .= $block->outertext;
                    }
                } else if ($block->tag == 'h2') {
                    $res .= '<h3>' . $block->innertext . '</h3>';
                } else if ($block->tag == 'blockquote') {
                    $res .= $block->outertext;
                } else if ($block->tag == 'hr' && $block->getAttribute('data-block') == 'sciam/raw_html') {
                    $res .= '<hr />';
                }
            }
        }

        $footer = $article->find('footer[class*="footer"]', 0);
        if ($footer) {
            $bios = $footer->find('div[class^=bio]');
            $bio = implode('', array_map(fn($b) => $b->innertext, $bios));
            $res .= $bio;
        }

        $item['content'] = $res;
        return $item;
    }
}
