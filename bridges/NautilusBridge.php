<?php

declare(strict_types=1);

class NautilusBridge extends FeedExpander
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Nautilus';
    const URI = 'https://nautil.us/';
    const DESCRIPTION = 'Returns the latest articles from Nautilus.';

    const PARAMETERS = [
        '' => [
            'limit' => [
                'name' => 'Feed Item Limit',
                'required' => true,
                'type' => 'number',
                'defaultValue' => 10,
                'title' => 'Maximum number of returned feed items. Default 10'
            ],
            'topic' => [
                'name' => 'Topics and Channels',
                'required' => false,
                'title' => 'Select a topic',
                'type' => 'list',
                'defaultValue' => 'default',
                'values' => [
                    'All items' => 'default',
                    'Topic: Anthropology' => 'topics/anthropology',
                    'Topic: Arts' => 'topics/arts',
                    'Topic: Astronomy' => 'topics/astronomy',
                    'Topic: Communication' => 'topics/communication',
                    'Topic: Economics' => 'topics/economics',
                    'Topic: Environment' => 'topics/environment',
                    'Topic: Evolution' => 'topics/evolution',
                    'Topic: General' => 'topics/general',
                    'Topic: Genetics' => 'topics/genetics',
                    'Topic: Geoscience' => 'topics/geoscience',
                    'Topic: Health' => 'topics/health',
                    'Topic: History' => 'topics/history',
                    'Topic: Math' => 'topics/math',
                    'Topic: Microbiology' => 'topics/microbiology',
                    'Topic: Neuroscience' => 'topics/neuroscience',
                    'Topic: Paleontology' => 'topics/paleontology',
                    'Topic: Philosophy' => 'topics/philosophy',
                    'Topic: Physics' => 'topics/physics',
                    'Topic: Psychology' => 'topics/psychology',
                    'Topic: Sociology' => 'topics/sociology',
                    'Topic: Technology' => 'topics/technology',
                    'Topic: Zoology' => 'topics/zoology',
                    'Channel: Art + Science' => 'channel/art-science',
                    'Channel: Biology + Beyond' => 'channel/bilology-beyond',
                    'Channel: Catalysts of Discovery' => 'channel/catalysts',
                    'Channel: Cosmos' => 'channel/cosmos',
                    'Channel: Culture' => 'channel/culture',
                    'Channel: Currents' => 'channel/currents',
                    'Channel: Earth' => 'channel/earth',
                    'Channel: Life' => 'channel/life',
                    'Channel: Mind' => 'channel/mind',
                    'Channel: Ocean' => 'channel/ocean',
                    'Channel: One Question' => 'channel/one-question',
                    'Channel: Quanta Abstractions' => 'channel/abstractions',
                    'Channel: Rewilding' => 'channel/rewilding',
                    'Channel: Science at the Ballot Box' => 'channel/ballotbox-science',
                    'Channel: Science Philanthropy Alliance' => 'channel/alliance',
                    'Channel: Spart of Science' => 'channel/spark',
                    'Channel: The Animal Issue' => 'channel/animal',
                    'Channel: The Climates Issue' => 'channel/climates',
                    'Channel: The Food Issue' => 'channel/food',
                    'Channel: The Kinship Issue' => 'channel/kinship',
                    'Channel: The Porthole' => 'channel/the-porthole',
                    'Channel: The Reality Issue' => 'channel/reality',
                    'Channel: The Rebel Issue' => 'channel/rebel',
                    'Channel: Women in Science & Engineering' => 'channel/wise'
                ]
            ]
        ],
    ];

    public function collectData()
    {
        $uri = self::URI;
        if ($this->getInput('topic') && $this->getInput('topic') != 'default') {
            $uri .= $this->getInput('topic') . '/';
        }
        $uri .= 'feed';

        $this->collectExpandableDatas($uri, (int)$this->getInput('limit'));
    }

    protected function parseItem(array $item)
    {
        $dom = getSimpleHTMLDOMCached($item['uri'], 7 * 24 * 60 * 60);
        $next_data = $this->extractNextData($dom);
        if ($next_data !== null) {
            $content = $this->parseNextDataContent($next_data);
            if ($content !== '') {
                $item['content'] = $content;
                return $item;
            }
        }

        $content = '';
        $feature_image = $dom->find('img.article-banner-img', 0);
        if ($feature_image) {
            $src = $feature_image->getAttribute('src');
            $content .= '<figure><img src="' . $src . '"></figure>';
        }

        $article_main = $dom->find('div.article-content', 0);
        if (!$article_main) {
            $item['content'] = $content;
            return $item;
        }

        $article_main = $this->prepareArticleContent($article_main);
        $content .= $article_main->innertext;

        $item['content'] = $content;
        return $item;
    }

    private function extractNextData($dom): ?array
    {
        $script = $dom->find('script#__NEXT_DATA__', 0);
        if (!$script) {
            $script = $dom->getElementById('__NEXT_DATA__');
        }

        if (!$script) {
            return null;
        }

        $decoded = json_decode(html_entity_decode($script->innertext), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function parseNextDataContent(array $next_data): string
    {
        $page_props = $next_data['props']['pageProps'] ?? [];
        $content = '';

        $featured_image = $page_props['post']['featuredImage']['node'] ?? [];
        $featured_image_url = $featured_image['sourceUrl'] ?? '';
        if ($featured_image_url !== '') {
            $content .= '<figure><img src="' . htmlspecialchars($featured_image_url) . '"';

            $featured_image_alt = trim($featured_image['altText'] ?? '');
            if ($featured_image_alt !== '') {
                $content .= ' alt="' . htmlspecialchars($featured_image_alt) . '"';
            }

            $content .= '></figure>';
        }

        $blocks = $page_props['blocks'] ?? ($page_props['post']['contentBlocks']['blocks'] ?? []);
        if (!is_array($blocks) || count($blocks) === 0) {
            return $content;
        }

        $article_html = $this->renderBlocks($blocks);
        if ($article_html === '') {
            return $content;
        }

        $article_main = str_get_html('<div>' . $article_html . '</div>');
        if (!$article_main) {
            return $content . defaultLinkTo($article_html, self::URI);
        }

        $article_main = $this->prepareArticleContent($article_main);

        return $content . $article_main->innertext;
    }

    private function prepareArticleContent($article_main)
    {
        // Mostly YouTube videos
        foreach ($article_main->find('iframe') as $iframe) {
            $iframe->outertext = '<a href="' . $iframe->src . '">' . $iframe->src . '</a>';
        }

        $article_main = defaultLinkTo($article_main, self::URI);

        $this->removeNodes($article_main->find('div.article-ad'));
        $this->removeNodes($article_main->find('div.primis-ad'));
        $this->removeNodes($article_main->find('div.article-collection_box'));
        $this->removeNodes($article_main->find('img[src*="nautilus-favicon-14.png"]'));

        foreach ($article_main->find('p') as $paragraph) {
            $text = trim(html_entity_decode($paragraph->plaintext, ENT_QUOTES | ENT_HTML5));
            if (strpos($text, 'Subscribe to our free newsletter') !== false) {
                $paragraph->outertext = '';
            }
        }

        return $article_main;
    }

    private function removeNodes($nodes): void
    {
        foreach ($nodes as $node) {
            if ($node->parent) {
                $node->parent->removeChild($node);
            }
        }
    }

    private function renderBlocks(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            if (!empty($block['innerHTML']) && is_string($block['innerHTML'])) {
                $html .= $block['innerHTML'];
                continue;
            }

            if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
                $html .= $this->renderBlocks($block['innerBlocks']);
                continue;
            }

            if (isset($block['blocks']) && is_array($block['blocks'])) {
                $html .= $this->renderBlocks($block['blocks']);
            }
        }

        return $html;
    }
}
