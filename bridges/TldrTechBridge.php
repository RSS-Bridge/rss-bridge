<?php

class TldrTechBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'TLDR Tech Newsletter Bridge';
    const URI = 'https://tldr.tech/';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Return newsletter articles from TLDR Tech';

    const PARAMETERS = [
        '' => [
            'limit' => [
                'name' => 'Maximum number of articles to return',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 10
            ],
            'topic' => [
                'name' => 'Topic',
                'type' => 'list',
                'values' => [
                    'Tech' => 'tech',
                    'Crypto' => 'crypto',
                    'AI' => 'ai',
                    'Web Dev' => 'engineering',
                    'Founders' => 'founders',
                    'Cybersecurity' => 'cybersecurity'
                ],
                'defaultValue' => 'tech'
            ]
        ]
    ];

    public function collectData()
    {
        $topic = $this->getInput('topic');
        $limit = $this->getInput('limit');
        $url = self::URI . $topic . '/archives';
        $html = getSimpleHTMLDOM($url);
        $entries_root = $html->find('div.content-center.mt-5', 0);
        $added = 0;
        foreach ($entries_root->children() as $child) {
            if ($child->tag != 'a') {
                continue;
            }
            // Convert /<topic>/2023-01-01 to unix timestamp
            $date_items = explode('/', $child->href);
            $date = strtotime(end($date_items));
            $this->items[] = [
                'uri'       => self::URI . $child->href,
                'title'     => $child->plaintext,
                'timestamp' => $date,
                'content'   => $this->extractContent(self::URI . $child->href),
            ];
            $added++;
            if ($added >= $limit) {
                break;
            }
        }
    }

    private function extractContent($url)
    {
        $html = getSimpleHTMLDOM($url);
        $content = $html->find('div.content-center.mt-5', 0);
        if (!$content) {
            return '';
        }
        $subscribe_form = $content->find('div.mt-5 > div > form', 0);
        if ($subscribe_form) {
            $content->removeChild($subscribe_form->parent->parent);
        }
        $privacy_link = $content->find("a[href='/privacy']", 0);
        if ($privacy_link) {
            $content->removeChild($privacy_link->parent->parent);
        }
        $headers = $content->find('h6.text-center.font-bold');
        foreach ($headers as $header) {
            $elem = $html->createElement('h3', $header->parent->plaintext);
            $elem->style = 'margin-top: 1.2em; margin-bottom: 0.5em;';
            $header_root = $header->parent;
            foreach ($header_root->children() as $child) {
                $header_root->removeChild($child);
            }
            $header_root->appendChild($elem);
        }

        foreach ($content->find('a.font-bold') as $a) {
            $a->removeAttribute('class');
            $elem = $html->createElement('b', $a->plaintext);
            $a->removeChild($a->firstChild());
            $a->appendChild($elem);
        }
        foreach ($content->children() as $child) {
            if ($child->tag != 'div') {
                continue;
            }
            foreach ($child->children() as $grandchild) {
                if ($grandchild->tag == 'div') {
                    $grandchild->style = 'margin-bottom: 12px;';
                }
            }
        }

        return $content->innertext;
    }
}
