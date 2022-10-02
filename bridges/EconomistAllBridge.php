<?php

class EconomistAllBridge extends FeedExpander
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Economist All';
    const URI = 'https://www.economist.com/';

    const CACHE_TIMEOUT = 60 * 60 * 3; // 3 hours
    const DESCRIPTION = 'Aggegate all feeds from the Economist';

    const PARAMETERS = [
        '' => [
            'limit' => [
                'name' => 'Limit number of items per feed',
                'required' => true,
                'type' => 'number',
                'defaultValue' => 10,
                'title' => 'Maximum number of returned feed items. Maximum 30, default 10'
            ],
            'addToTitle' => [
                'name' => 'Add categories to title',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],
            'addContents' => [
                'name' => 'Also fetch contents for articles',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],
            'filterDays' => [
                'name' => 'Maximum age of the article (days)',
                'type' => 'number',
                'defaultValue' => 14,
                'title' => 'Do not show articles older than this value'
            ],
            'onlyAudio' => [
                'name' => 'Only fetch articles with an audio version',
                'type' => 'checkbox',
            ]
        ]
    ];

    const FEEDS = [
        // Normal feeds
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
        // Blogs
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
        'The Economist Explains' => 'the-economist-explains'
    ];

    const SECTION_PRIORITIES = [
        '[The world this week] Politics' => 1,
        '[The world this week] Business' => 2,
        '[The world this week] KAL’s cartoon' => 3
    ];

    private function getContents($uri)
    {
        $html = getSimpleHTMLDOMCached($uri);
        $article = $html->find('#new-article-template', 0);
        if ($article == null) {
            $article = $html->find('main', 0);
        }
        if ($article == null) {
            return '<b>Error</b>';
        }
        $elem = $article->find('div', 0);
        // $elem = $div1->find('div', 0);
        // $div3 = $div2->find('div', 2);
        return self::processContent($html, $elem);
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
            $small->outertext = $small->innertext;
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
            if (!str_contains($strange_img->src, 'https://economist.com')) {
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


    public function collectData()
    {
        set_time_limit(600);
        $allItems = [];
        $tags = [];
        // limit the returned articles to 30 at max
        if ((int)$this->getInput('limit') <= 30) {
            $limit = (int)$this->getInput('limit');
        } else {
            $limit = 30;
        }

        foreach (self::FEEDS as $tag => $url) {
            if ($url == 'latest' && $this->getInput('onlyAudio') == 1) {
                continue;
            }
            $this->collectExpandableDatas('https://www.economist.com/' . $url . '/rss.xml', $limit);
            foreach ($this->items as $item) {
                $allItems[$item['uri']] = $item;
                if (array_key_exists($item['uri'], $tags)) {
                    array_push($tags[$item['uri']], $tag);
                } else {
                    $tags[$item['uri']] = [$tag];
                }
            }
            $this->items = [];
        }

        $feedIndices = [];
        $feeds = array_keys(self::FEEDS);
        for ($i = 0; $i < count($feeds); $i++) {
            $feedIndices[$feeds[$i]] = $i;
        }

        $today = new Datetime();
        $today->setTime(0, 0, 0, 0);
        if ($this->getInput('filterDays') != null) {
            $max_age = $today->getTimestamp() - $this->getInput('filterDays') * (60 * 60 * 24);
        } else {
            $max_age = $today->getTimestamp() - (60 * 60 * 24 * 365);
        }

        foreach ($allItems as $uri => $item) {
            $item['categories'] = $tags[$uri];
            if ($this->getInput('addToTitle') == 1) {
                $item['title'] = '[' . join(', ', $tags[$uri]) . '] ' . $item['title'];
            }
            $item['feedIndex'] = $feedIndices[$tags[$uri][0]];
            if ($item['timestamp'] < $max_age) {
                continue;
            }
            if ($this->getInput('addContents') == 1) {
                list($content, $audio_url) = $this->getContents($uri);
                $item['content'] = $content;
                if ($audio_url != null) {
                    $item['enclosures'] = [$audio_url];
                } else if ($this->getInput('onlyAudio') == 1) {
                    continue;
                }
            }
            // There's a small delta between timestamps in different
            // feeds, and that screws the intended section ordering in
            // the journal. So timestamps are rounded to days.  It's
            // not the kind of publication where the exact time is
            // relevant anyway.
            $item['timestamp'] = $item['timestamp'] - ($item['timestamp'] % (60 * 60 * 24));
            $this->items[] = $item;
        }

        usort($this->items, function ($item1, $item2) {
            if ($item1['timestamp'] == $item2['timestamp']) {
                $idx1 = $item1['feedIndex'];
                $idx2 = $item2['feedIndex'];
                if ($idx1 == $idx2 && $this->getInput('addToTitle') == 1) {
                    $pr_1 = self::SECTION_PRIORITIES[$item1['title']] ?? 100;
                    $pr_2 = self::SECTION_PRIORITIES[$item2['title']] ?? 100;
                    return $pr_1 > $pr_2;
                }
                return $idx1 > $idx2;
            }
            return $item1['timestamp'] < $item2['timestamp'];
        });
    }
}
