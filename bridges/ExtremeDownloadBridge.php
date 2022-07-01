<?php

class ExtremeDownloadBridge extends BridgeAbstract
{
    const NAME = 'Extreme Download';
    const URI = 'https://www.extreme-down.plus/';
    const DESCRIPTION = 'Suivi de série sur Extreme Download';
    const MAINTAINER = 'sysadminstory';
    const PARAMETERS = [
        'Suivre la publication des épisodes d\'une série en cours de diffusion' => [
            'url' => [
                'name' => 'URL de la série',
                'type' => 'text',
                'required' => true,
                'title' => 'URL d\'une série sans le https://www.extreme-down.plus/',
                'exampleValue' => 'series-hd/hd-series-vostfr/46631-halt-and-catch-fire-saison-04-vostfr-hdtv-720p.html'],
            'filter' => [
                'name' => 'Type de contenu',
                'type' => 'list',
                'title' => 'Type de contenu à suivre : Téléchargement, Streaming ou les deux',
                'values' => [
                    'Streaming et Téléchargement' => 'both',
                    'Téléchargement' => 'download',
                    'Streaming' => 'streaming'
                    ]
                ]
            ]
        ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . $this->getInput('url'));

        $filter = $this->getInput('filter');

        $typesText = [
            'download' => 'Téléchargement',
            'streaming' => 'Streaming'
        ];

        // Get the TV show title
        $this->showTitle = trim($html->find('span[id=news-title]', 0)->plaintext);

        $list = $html->find('div[class=prez_7]');
        foreach ($list as $element) {
            $add = false;
            // Link type is needed is needed to generate an unique link
            $type = $this->findLinkType($element);
            if ($filter == 'both') {
                $add = true;
            } else {
                if ($type == $filter) {
                    $add = true;
                }
            }
            if ($add == true) {
                $item = [];

                // Get the element name
                $title = $element->plaintext;

                // Get thee element links
                $links = $element->next_sibling()->innertext;

                $item['content'] = $links;
                $item['title'] = $this->showTitle . ' ' . $title . ' - ' . $typesText[$type];
                // As RSS Bridge use the URI as GUID they need to be unique : adding a md5 hash of the title element
                // should geneerate unique URI to prevent confusion for RSS readers
                $item['uri'] = self::URI . $this->getInput('url') . '#' . hash('md5', $item['title']);

                $this->items[] = $item;
            }
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Suivre la publication des épisodes d\'une série en cours de diffusion':
                return $this->showTitle . ' - ' . self::NAME;
            break;
            default:
                return self::NAME;
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Suivre la publication des épisodes d\'une série en cours de diffusion':
                return self::URI . $this->getInput('url');
            break;
            default:
                return self::URI;
        }
    }

    private function findLinkType($element)
    {
        $return = '';
        // Walk through all elements in the reverse order until finding one with class 'presz_2'
        while ($element->class != 'prez_2') {
            $element = $element->prev_sibling();
        }
        $text = html_entity_decode($element->plaintext);

        // Regarding the text of the element, return the according link type
        if (stristr($text, 'téléchargement') != false) {
            $return = 'download';
        } elseif (stristr($text, 'streaming') != false) {
            $return = 'streaming';
        }

        return $return;
    }
}
