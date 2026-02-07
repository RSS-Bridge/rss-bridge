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
        $content = '';

        $dom = getSimpleHTMLDOMCached($item['uri'], 7 * 24 * 60 * 60);
        $feature_image = $dom->find('img.article-banner-img', 0);
        if ($feature_image) {
            $src = $feature_image->getAttribute('src');
            $content .= '<figure><img src="' . $src . '"></figure>';
        }

        $article_main = $dom->find('div.article-content', 0);

        // Mostly YouTube videos
        $iframes = $article_main->find('iframe');
        foreach ($iframes as $iframe) {
            $iframe->outertext = '<a href="' . $iframe->src . '">' . $iframe->src . '</a>';
        }

        $article_main = defaultLinkTo($article_main, self::URI);

        $ads = $article_main->find('div.article-ad');
        foreach ($ads as $ad) {
            $ad->parent->removeChild($ad);
        }
        $ads = $article_main->find('div.primis-ad');
        foreach ($ads as $ad) {
            $ad->parent->removeChild($ad);
        }
        $blocks = $article_main->find('div.article-collection_box');
        foreach ($blocks as $block) {
            $block->parent->removeChild($block);
        }
        $content .= $article_main->innertext;

        $item['content'] = $content;
        return $item;
    }
}
