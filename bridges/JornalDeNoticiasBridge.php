<?php

class JornalDeNoticiasBridge extends BridgeAbstract
{
    const NAME = 'Jornal de Notícias (PT)';
    const URI = 'https://jn.pt';
    const DESCRIPTION = 'Jornal de Notícias (JN.PT)';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
        'URL' => [
            'url' => [
                'name' => 'URL (relative)',
                'exampleValue' => 'opiniao/catia-domingues.html',
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://static.globalnoticias.pt/jn/common/images/favicons/favicon-128.png';
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'URL':
                $url = self::URI . '/' . $this->getInput('url');
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

            $title = $element->find('h2 a', 0);
            $link = $element->find('h2 a', 0);
            $auth = $element->find('h3 a', 0);

            $item['title'] = $title->plaintext;
            $item['uri'] = self::URI . $link->href;
            $item['author'] = $auth->plaintext;

            $snippet = $element->find('h4 a', 0);
            if ($snippet) {
                $item['content'] = $snippet->plaintext;
            }

            $this->items[] = $item;
        }
    }
}
