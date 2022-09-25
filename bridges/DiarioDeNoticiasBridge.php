<?php

class DiarioDeNoticiasBridge extends BridgeAbstract
{
    const NAME = 'Diário de Notícias (PT)';
    const URI = 'https://dn.pt';
    const DESCRIPTION = 'Diário de Notícias (DN.PT)';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
        'Tag' => [
            'n' => [
                'name' => 'Tag Name',
                'required' => true,
                'exampleValue' => 'rogerio-casanova',
            ]
        ]
    ];

    const MONPT = [
        'jan',
        'fev',
        'mar',
        'abr',
        'mai',
        'jun',
        'jul',
        'ago',
        'set',
        'out',
        'nov',
        'dez',
    ];

    public function getIcon()
    {
        return 'https://static.globalnoticias.pt/dn/common/images/favicons/favicon-128.png';
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Tag':
                $name = self::NAME . ' | Tag | ' . $this->getInput('n');
                break;
            default:
                $name = self::NAME;
        }
        return $name;
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Tag':
                $url = self::URI . '/tag/' . $this->getInput('n') . '.html';
                break;
            default:
                $url = self::URI;
        }
        return $url;
    }

    public function collectData()
    {
        $archives = $this->getURI();
        $html = getSimpleHTMLDOMCached($archives);

        foreach ($html->find('article') as $element) {
            $item = [];

            $title = $element->find('.t-am-title', 0);
            $link = $element->find('a.t-am-text', 0);

            $item['title'] = $title->plaintext;
            $item['uri'] = self::URI . $link->href;

            $snippet = $element->find('.t-am-lead', 0);
            if ($snippet) {
                $item['content'] = $snippet->plaintext;
            }
            preg_match('|edicao-do-dia\\/(?P<day>\d\d)-(?P<monpt>\w\w\w)-(?P<year>\d\d\d\d)|', $link->href, $d);
            if ($d) {
                $item['timestamp'] = sprintf('%s-%s-%s', $d['year'], array_search($d['monpt'], self::MONPT) + 1, $d['day']);
            }

            $this->items[] = $item;
        }
    }
}
