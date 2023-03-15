<?php

class FreeTelechargerBridge extends BridgeAbstract
{
        const NAME = 'Free-Telecharger';
        const URI = 'https://www.free-telecharger.live/';
        const DESCRIPTION = 'Suivi de série sur Free-Telecharger';
        const MAINTAINER  = 'sysadminstory';
        const PARAMETERS = [
                'Suivi de publication de série' => [
                        'url' => [
                                'name' => 'URL de la série',
                                'type' => 'text',
                                'required' => true,
                                'title' => 'URL d\'une série sans le https://www.free-telecharger.live/',
                                'pattern' => 'series.*\.html',
                                'exampleValue' => 'series-vf-hd/145458-the-last-of-us-saison-1-web-dl-720p.html'
                        ],
                ]
        ];
        const CACHE_TIMEOUT = 3600;
        public function collectData()
        {
                $html = getSimpleHTMLDOM(self::URI . $this->getInput('url'));

                // Find all block content of the page
                $blocks = $html->find('div[class=block1]');

                // Global Infos block
                $infosBlock = $blocks[0];
                // Links block
                $linksBlock = $blocks[2];

                // Extract Global Show infos
                $this->showTitle = trim($infosBlock->find('div[class=titre1]', 0)->find('font', 0)->plaintext);
                $this->showTechDetails = trim($infosBlock->find('div[align=center]', 0)->find('b', 0)->plaintext);



                // Get Episodes names and links
                $episodes = $linksBlock->find('div[id=link]', 0)->find('font[color=#ff6600]');
                $links = $linksBlock->find('div[id=link]', 0)->find('a');

            foreach ($episodes as $index => $episode) {
                        $item = []; // Create an empty item
                        $item['title'] = $this->showTitle . ' ' . $this->showTechDetails . '  - ' . ltrim(trim($episode->plaintext), '-');
                        $item['uri'] = $links[$index]->href;
                        $item['content'] = '<a href="' . $item['uri'] . '">' . $item['title'] . '</a>';
                        $item['uid'] = hash('md5', $item['uri']);

                        $this->items[] = $item; // Add this item to the list
            }
        }

        public function getName()
        {
            switch ($this->queriedContext) {
                case 'Suivi de publication de série':
                    return $this->showTitle . ' ' . $this->showTechDetails . ' - ' . self::NAME;
                break;
                default:
                    return self::NAME;
            }
        }

        public function getURI()
        {
            switch ($this->queriedContext) {
                case 'Suivi de publication de série':
                    return self::URI . $this->getInput('url');
                break;
                default:
                    return self::URI;
            }
        }

        public function detectParameters($url)
        {
                // Example: https://www.free-telecharger.live/series-vf-hd/145458-the-last-of-us-saison-1-web-dl-720p.html

                $params = [];
                $regex = '/^https:\/\/www.*\.free-telecharger\.live\/(series.*\.html)/';
            if (preg_match($regex, $url, $matches) > 0) {
                        $params['url'] = urldecode($matches[1]);
                        return $params;
            }

                return null;
        }
}
