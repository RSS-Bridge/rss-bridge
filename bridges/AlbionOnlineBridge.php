<?php

class AlbionOnlineBridge extends BridgeAbstract
{
    const NAME = 'Albion Online Changelog';
    const MAINTAINER = 'otakuf';
    const URI = 'https://albiononline.com';
    const DESCRIPTION = 'Returns the changes made to the Albion Online';
    const CACHE_TIMEOUT = 3600; // 60min

    const PARAMETERS = [ [
        'postcount' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => true,
            'title' => 'Maximum number of items to return',
            'defaultValue' => 5,
        ],
        'language' => [
            'name' => 'Language',
            'type' => 'list',
            'values' => [
                'English' => 'en',
                'Deutsch' => 'de',
                'Polski' => 'pl',
                'Français' => 'fr',
                'Русский' => 'ru',
                'Português' => 'pt',
                'Español' => 'es',
             ],
            'title' => 'Language of changelog posts',
            'defaultValue' => 'en',
        ],
        'full' => [
            'name' => 'Full changelog',
            'type' => 'checkbox',
            'required' => false,
            'title' => 'Enable to receive the full changelog post for each item'
        ],
    ]];

    public function collectData()
    {
        $api = 'https://albiononline.com/';
        // Example: https://albiononline.com/en/changelog/1/5
        $url = $api . $this->getInput('language') . '/changelog/1/' . $this->getInput('postcount');

        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('li') as $data) {
            $item = [];
            $item['uri'] = self::URI . $data->find('a', 0)->getAttribute('href');
            $item['title'] = trim(explode('|', $data->find('span', 0)->plaintext)[0]);
            // Time below work only with en lang. Need to think about solution. May be separate request like getFullChangelog, but to english list for all language
            //print_r( date_parse_from_format( 'M j, Y' , 'Sep 9, 2020') );
            //$item['timestamp'] = $this->extractDate($a->plaintext);
            $item['author'] = 'albiononline.com';
            if ($this->getInput('full')) {
                $item['content'] = $this->getFullChangelog($item['uri']);
            } else {
                //$item['content'] = trim(preg_replace('/\s+/', ' ', $data->find('span', 0)->plaintext));
                // Just use title, no info at all or use title and date, see above
                $item['content'] = $item['title'];
            }
            $item['uid'] = hash('sha256', $item['title']);
            $this->items[] = $item;
        }
    }

    private function getFullChangelog($url)
    {
        $html = getSimpleHTMLDOMCached($url);
        $html = defaultLinkTo($html, self::URI);
        return $html->find('div.small-12.columns', 1)->innertext;
    }
}
