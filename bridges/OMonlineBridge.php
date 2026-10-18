<?php

class OMonlineBridge extends BridgeAbstract
{
    const NAME        = 'OM Online';
    const URI         = 'https://www.om-online.de';
    const DESCRIPTION = 'RSS feed for OM Online';
    const MAINTAINER  = 'jummo4@yahoo.de';
    const PARAMETERS = [
    [
        'ort' => [
            'name' => 'Ort',
            'type' => 'list',
            'title' => 'Ort auswählen',
            'values' => [
                'Alle Orte' => [
                    'Alle Orte' => NULL,
                ],
                'Gebiet Cloppenburg' => [
                    'Barßel' => 'landkreis-cloppenburg/barssel',
                    'Cappeln' => 'landkreis-cloppenburg/cappeln',
                    'Emstek' => 'landkreis-cloppenburg/emstek',
                    'Friesoythe' => 'landkreis-cloppenburg/friesoythe',
                    'Lastrup' => 'landkreis-cloppenburg/lastrup',
                    'Löningen' => 'landkreis-cloppenburg/loeningen',
                    'Saterland' => 'landkreis-cloppenburg/saterland',
                    'Bösel' => 'landkreis-cloppenburg/boesel',
                    'Cloppenburg' => 'landkreis-cloppenburg/cloppenburg',
                    'Essen' => 'landkreis-cloppenburg/essen',
                    'Garrel' => 'landkreis-cloppenburg/garrel',
                    'Lindern' => 'landkreis-cloppenburg/lindern',
                    'Molbergen' => 'landkreis-cloppenburg/molbergen',
                ],
                'Gebiet Vechta' => [
                    'Bakum' => 'landkreis-vechta/bakum',
                    'Dinklage' => 'landkreis-vechta/dinklage',
                    'Holdorf' => 'landkreis-vechta/holdorf',
                    'Neuenkirchen-Vörden' => 'landkreis-vechta/neuenkirchen-voerden',
                    'Vechta' => 'landkreis-vechta/vechta',
                    'Damme' => 'landkreis-vechta/damme',
                    'Goldenstedt' => 'landkreis-vechta/goldenstedt',
                    'Lohne' => 'landkreis-vechta/lohne',
                    'Steinfeld' => 'landkreis-vechta/steinfeld',
                    'Visbek' => 'landkreis-vechta/visbek',
                ],
                'Nachbarschaft' => [
                    'Nachbarschaft' => 'nachbarschaft',
                ],
            ]
	],
    ]
    ];

    public function collectData()
    {
		// construct URL
		$ort = $this->getInput('ort');
		if (empty($ort)) {
			$url = self::URI;
		} else {
			$url = sprintf('%s/%s', self::URI, $ort);
		}

        $html = getSimpleHTMLDOM($url);

        $html = defaultLinkTo($html, $url);

		// only top sectino
		$section = $html->find('section.content--group', 0);

		// loop over all teaser
		$teasers = $section ? $section->find('div.article__teaser') : [];

		foreach ($teasers as $teaser) {
			// skip advertorials
			if (strpos($teaser->class, 'article__teaser--advertorial') !== false) {
				continue;
			}
	
			$item = [];
	
			// link to article
			$a = $teaser->find('a.article__teaser__sub-headline--wrapper', 0)
				?? $teaser->find('a[href*="/artikel/"]', 0);
			if ($a === null) {
				continue;
			}
			$item['uri'] = $a->href;
	
			// header
			$heading = $teaser->find('h3.article__teaser__headline', 0);
			$item['title'] = $heading
				? trim($heading->plaintext)
				: trim($a->title ?? $teaser->plaintext);
	
			// image
			$content = '';
			$img = $teaser->find('a.article__teaser__image figure img', 0);
			if ($img !== null) {
				$src = trim($img->getAttribute('src') ?? '');
				if ($src !== '' && strpos($src, '/static/image/icons/') === false) {
					$alt = htmlspecialchars($img->getAttribute('alt') ?? '', ENT_QUOTES);
					$content = sprintf('<p><img src="%s" alt="%s"></p>', $src, $alt);
				}
			}
	
			// teaser text
			if ($intro = $teaser->find('div.article__teaser__intro', 0)) {
				$introText = trim($intro->plaintext);
				if ($introText !== '') {
					$content .= '<p>' . $introText . '</p>';
				}
			}
			$item['content'] = $content;
	
			// date
			$item['timestamp'] = $teaser->getAttribute('data-updated-at') ?? '';
	
			$this->items[] = $item;
	
			// maximum of six items are availabel
			if (count($this->items) >= 6) {
				break;
			}
		}
	}

