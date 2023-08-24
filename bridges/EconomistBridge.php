<?php

class EconomistBridge extends FeedExpander
{
    const MAINTAINER = 'bockiii, sqrtminusone';
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
                    'The Economist Reads' => 'the-economist-reads',
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
        $html = getSimpleHTMLDOM($item['uri']);

        $article = $html->find('#new-article-template', 0);
        if ($article == null) {
            $article = $html->find('main', 0);
        }
        if ($article) {
            $elem = $article->find('div', 0);
            list($content, $audio_url) = $this->processContent($html, $elem);
            $item['content'] = $content;
            if ($audio_url != null) {
                $item['enclosures'] = [$audio_url];
            }
        }
        return $item;
    }

    private function processContent($html, $elem)
    {
        // Remove extra styles
        $styles = $elem->find('style');
        foreach ($styles as $style) {
            $style->parent->removeChild($style);
        }

        // Remove the section with remaining articles
        $more_elem = $elem->find('h2.ds-section-headline.ds-section-headline--rule-emphasised', 0);
        if ($more_elem != null) {
            if ($more_elem->parent && $more_elem->parent->parent) {
                $more_elem->parent->parent->removeChild($more_elem->parent);
            }
        }

        // Remove 'capitalization' with <small> tags
        foreach ($elem->find('small') as $small) {
            $small->outertext = strtoupper($small->innertext);
        }

        // Extract audio
        $audio_url = null;
        $audio_elem = $elem->find('#audio-player', 0);
        if ($audio_elem != null) {
            $audio_url = $audio_elem->src;
            $audio_elem->parent->parent->removeChild($audio_elem->parent);
        }

        // No idea how this works on the original site
        foreach ($elem->find('img') as $img) {
            $img->removeAttribute('width');
            $img->removeAttribute('height');
        }

        // Some hacks for 'interactive' sections to make them a bit
        // more readable. Here's one example:
        // https://www.economist.com/interactive/briefing/2022/09/24/war-in-ukraine-has-reshaped-worlds-fuel-markets
        $svelte = $elem->find('svelte-scroller-outer', 0);
        if ($svelte != null) {
            $svelte->parent->removeChild($svelte);
        }
        foreach ($elem->find('img') as $strange_img) {
            if (!str_contains($strange_img->src, 'economist.com')) {
                $strange_img->src = 'https://economist.com' . $strange_img->src;
            }
        }
        // Trying to fix interactive infographics. This doesn't look
        // quite as well, but fortunately, such elements are rare
        // (~95% of infographics are plain images)
        foreach ($elem->find('div.ds-image') as $ds_img) {
            $ds_img->style = 'max-width: min(100%, 700px); overflow: hidden; margin: 2rem auto;';
            $g_artboard = null;
            foreach ($ds_img->find('div.g-artboard') as $g_artboard_cand) {
                if (!str_contains($g_artboard_cand->style, 'display: none')) {
                    $g_artboard = $g_artboard_cand;
                }
            }
            if ($g_artboard != null) {
                $g_artboard->style = $g_artboard->style . 'position: relative;';
                $img = $g_artboard->find('img', 0);
                if ($img != null) {
                    $img->style = 'top: 0; display: block; width: 100% !important;';
                    foreach ($g_artboard->find('div') as $div) {
                        if ($div->style == null) {
                            $div->style = 'position: absolute;';
                        } else {
                            $div->style = $div->style . 'position: absolute';
                        }
                    }
                }
            }
        }

        $vertical = $elem->find('div[data-test-id=vertical]', 0);
        if ($vertical != null) {
            $vertical->parent->removeChild($vertical);
        }

        // Section with 'Save', 'Share' and 'Give buttons'
        foreach ($elem->find('div[data-test-id=sharing-modal]') as $sharing) {
            $sharing->parent->removeChild($sharing);
        }
        // These links become HUGE without <style> tags and aren't
        // particularly useful anyhow
        foreach ($elem->find('a.ds-link-with-arrow-icon') as $a) {
            $a->parent->removeChild($a);
        }

        // The Economist puts infographics into iframes, which doesn't
        // work in any of my readers. So this replaces iframes with
        // links.
        foreach ($elem->find('iframe') as $iframe) {
            $a = $html->createElement('a');
            $a->href = $iframe->src;
            $a->innertext = $iframe->src;
            $iframe->parent->appendChild($a);
            $iframe->parent->removeChild($iframe);
        }

        // Using <section> tags does nothing except interfering with
        // rss-bridge styles, so this replaces them with <div>
        $res = $elem->innertext;
        $res = str_replace('<section', '<div', $res);
        $res = str_replace('</section', '</div', $res);
        $content = '<div>' . $res . '</div>';
        return [$content, $audio_url];
    }
}
