<?php

declare(strict_types=1);

class TldrTechBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'TLDR Tech Newsletter Bridge';
    const URI = 'https://tldr.tech/';
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
                    'Web Dev' => 'webdev',
                    'AI' => 'ai',
                    'Information Security' => 'infosec',
                    'Product Management' => 'product',
                    'DevOps' => 'devops',
                    'Crypto' => 'crypto',
                    'Design' => 'design',
                    'Marketing' => 'marketing',
                    'Founders' => 'founders',
                ],
                'defaultValue' => 'tech'
            ]
        ]
    ];

    public function collectData()
    {
        $topic = $this->getInput('topic');
        $limit = $this->getInput('limit');

        $url = self::URI . 'api/latest/' . $topic;
        $response = getContents($url, [], [], true);
        $location = $response->getHeader('Location');
        $locationUrl = Url::fromString($location);

        $this->extractItem($locationUrl);

        $archives_url = self::URI . $topic . '/archives';
        $archives_html = getSimpleHTMLDOM($archives_url);
        $entries_root = $archives_html->find('div.content-center.mt-5', 0);
        foreach ($entries_root->children() as $child) {
            if ($child->tag != 'a') {
                continue;
            }
            $this->extractItem(Url::fromString(self::URI . $child->href));
            if (count($this->items) >= $limit) {
                break;
            }
        }
    }

    private function extractItem(Url $url)
    {
        $pathParts = explode('/', $url->getPath());
        $date = strtotime(end($pathParts));
        try {
            [$content, $title] = $this->extractContent($url);

            $this->items[] = [
                'uri'       => (string) $url,
                'title'     => $title,
                'timestamp' => $date,
                'content'   => $content,
            ];
        } catch (HttpException $e) {
            // archive occasionally returns broken URLs
            return;
        }
    }

    private function extractContent($url)
    {
        $html = getSimpleHTMLDOMCached($url);
        $content = $html->find('div.content-center.mt-5', 0);
        if (!$content) {
            throw new \Exception('Could not find content');
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
        $title = $content->find('h2', 0);
        return [$content->innertext, $title->plaintext];
    }
}
