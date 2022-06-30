<?php

class EconomistBridge extends FeedExpander
{
    const MAINTAINER = 'bockiii';
    const NAME = 'Economist Bridge';
    const URI = 'https://www.economist.com/';
    const CACHE_TIMEOUT = 3600; //1hour
    const DESCRIPTION = 'Returns the latest articles for the selected category';

    const PARAMETERS = [
        'global' => [
            'limit' => [
                'name' => 'Feed Item Limit',
                'required' => true,
                'type' => 'number',
                'defaultValue' => 10,
                'title' => 'Maximum number of returned feed items. Maximum 30, default 10'
            ]
        ],
        'Topics' => [
            'topic' => [
                'name' => 'Topics',
                'type' => 'list',
                'title' => 'Select a Topic',
                'defaultValue' => 'latest',
                'values' => [
                    'Latest' => 'latest',
                    'The world this week' => 'the-world-this-week',
                    'Letters' => 'letters',
                    'Leaders' => 'leaders',
                    'Briefings' => 'briefing',
                    'Special reports' => 'special-report',
                    'Britain' => 'britain',
                    'Europe' => 'europe',
                    'United States' => 'united-states',
                    'The Americas' => 'the-americas',
                    'Middle East and Africa' => 'middle-east-and-africa',
                    'Asia' => 'asia',
                    'China' => 'china',
                    'International' => 'international',
                    'Business' => 'business',
                    'Finance and economics' => 'finance-and-economics',
                    'Science and technology' => 'science-and-technology',
                    'Books and arts' => 'books-and-arts',
                    'Obituaries' => 'obituary',
                    'Graphic detail' => 'graphic-detail',
                    'Indicators' => 'economic-and-financial-indicators',
                ]
            ]
        ],
        'Blogs' => [
            'blog' => [
                'name' => 'Blogs',
                'type' => 'list',
                'title' => 'Select a Blog',
                'values' => [
                    'Bagehots notebook' => 'bagehots-notebook',
                    'Bartleby' => 'bartleby',
                    'Buttonwoods notebook' => 'buttonwoods-notebook',
                    'Charlemagnes notebook' => 'charlemagnes-notebook',
                    'Democracy in America' => 'democracy-in-america',
                    'Erasmus' => 'erasmus',
                    'Free exchange' => 'free-exchange',
                    'Game theory' => 'game-theory',
                    'Gulliver' => 'gulliver',
                    'Kaffeeklatsch' => 'kaffeeklatsch',
                    'Prospero' => 'prospero',
                    'The Economist Explains' => 'the-economist-explains',
                ]
            ]
        ]
    ];

    public function collectData()
    {
        // get if topics or blogs were selected and store the selected category
        switch ($this->queriedContext) {
            case 'Topics':
                $category = $this->getInput('topic');
                break;
            case 'Blogs':
                $category = $this->getInput('blog');
                break;
            default:
                $category = 'latest';
        }
        // limit the returned articles to 30 at max
        if ((int)$this->getInput('limit') <= 30) {
            $limit = (int)$this->getInput('limit');
        } else {
            $limit = 30;
        }

        $this->collectExpandableDatas('https://www.economist.com/' . $category . '/rss.xml', $limit);
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);
        $article = getSimpleHTMLDOM($item['uri']);
        // before the article can be added, it needs to be cleaned up, thus, the extra function
        // We also need to distinguish between old style and new style articles
        if ($article->find('article', 0)->getAttribute('data-test-id') == 'Article') {
            $contentNode = 'div.layout-article-body';
            $imgNode = 'div.article__lead-image';
            $categoryNode = 'span.article__subheadline';
        } elseif ($article->find('article', 0)->getAttribute('data-test-id') === 'NewArticle') {
            $contentNode = 'section';
            $imgNode = 'figure.css-12eysrk.e3y6nua0';
            $categoryNode = 'span.ern1uyf0';
        } else {
            return;
        }

        $item['content'] = $this->cleanContent($article, $contentNode);
        // only the article lead image is retained if it's there
        if (!is_null($article->find($imgNode, 0))) {
            $item['enclosures'][] = $article->find($imgNode, 0)->find('img', 0)->getAttribute('src');
        } else {
            $item['enclosures'][] = '';
        }
        // add the subheadline as category. This will create a link in new articles
        // and a text in old articles
        $item['categories'][] = $article->find($categoryNode, 0)->innertext;

        return $item;
    }

    private function cleanContent($article, $contentNode)
    {
        // the actual article is in this div
        $content = $article->find($contentNode, 0)->innertext;
        // clean the article content. Remove all div's since the text is in paragraph elements
        foreach (
            [
            '<div '
            ] as $tag_start
        ) {
            $content = stripRecursiveHTMLSection($content, 'div', $tag_start);
        }
        // now remove embedded iframes. The podcast postings contain these for example
        $content = preg_replace('/<iframe.*?\/iframe>/i', '', $content);
        // fix the relative links
        $content = defaultLinkTo($content, $this->getURI());

        return $content;
    }
}
