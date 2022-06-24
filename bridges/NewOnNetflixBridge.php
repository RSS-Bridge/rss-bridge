<?php

class NewOnNetflixBridge extends BridgeAbstract
{
    const NAME = 'NewOnNetflix removals bridge';
    const URI = 'https://www.newonnetflix.info';
    const DESCRIPTION = 'Upcoming removals from Netflix (NewOnNetflix already provides additions as RSS)';
    const MAINTAINER = 'jdesgats';
    const PARAMETERS = array(array(
        'country' => array(
            'name' => 'Country',
            'type' => 'list',
            'values' => array(
                'Australia/New Zealand' => 'anz',
                'Canada' => 'can',
                'United Kingdom' => 'uk',
                'United States' => 'usa',
            ),
            'defaultValue' => 'uk',
        )
    ));
    const CACHE_TIMEOUT = 3600 * 24;

    public function collectData()
    {
        $baseURI = 'https://' . $this->getInput('country') . '.newonnetflix.info';
        $html = getSimpleHTMLDOMCached($baseURI . '/lastchance', self::CACHE_TIMEOUT);

        foreach ($html->find('article.oldpost') as $element) {
            $title = $element->find('a.infopop[title]', 0);
            $img = $element->find('img[lazy_src]', 0);
            $date = $element->find('span[title]', 0);

            // format sholud be 'dd/mm/yy - dd/mm/yy'
            // (the added date might be "unknown")
            $fromTo = array();
            if (preg_match('/^\s*(.*?)\s*-\s*(.*?)\s*$/', $date->title, $fromTo)) {
                $from = $fromTo[1];
                $to = $fromTo[2];
            } else {
                $from = 'unknown';
                $to = 'unknown';
            }
            $summary = <<<EOD
				<img src="{$img->lazy_src}" loading="lazy">
				<div>{$title->title}</div>
				<div><strong>Added on:</strong>$from</div>
				<div><strong>Removed on:</strong>$to</div>
EOD;

            $item = array();
            $item['uri'] = $baseURI . $title->href;
            $item['title'] = $to . ' - ' . $title->plaintext;
            $item['content'] = $summary;
            // some movies are added and removed multiple times
            $item['uid'] = $title->href . '-' . $to;
            $this->items[] = $item;
        }
    }
}
