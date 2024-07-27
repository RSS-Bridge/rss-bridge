<?php

class EconomistWorldInBriefBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Economist the World in Brief Bridge';
    const URI = 'https://www.economist.com/the-world-in-brief';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Returns stories from the World in Brief section';

    const CONFIGURATION = [
        'cookie' => [
            'required' => false,
        ]
    ];

    const PARAMETERS = [
        '' => [
            'splitGobbets' => [
                'name' => 'Split the short stories',
                'type' => 'checkbox',
                'defaultValue' => false,
                'title' => 'Whether to split the short stories into separate entries'
            ],
            'limit' => [
                'name' => 'Truncate headers for the short stories',
                'type' => 'number',
                'defaultValue' => 100
            ],
            'agenda' => [
                'name' => 'Add agenda for the day',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],
            'agendaPictures' => [
                'name' => 'Include pictures to the agenda',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],
            'quote' => [
                'name' => 'Include the quote of the day',
                'type' => 'checkbox'
            ]
        ]
    ];

    public function collectData()
    {
        $headers = [];
        if ($this->getOption('cookie')) {
            $headers = [
                'Authority: www.economist.com',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-language: en-US,en;q=0.9',
                'Cache-control: max-age=0',
                'Cookie: ' . $this->getOption('cookie'),
                'Upgrade-insecure-requests: 1',
                'User-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
            ];
        }
        $html = getSimpleHTMLDOM(self::URI, $headers);
        $gobbets = $html->find('._gobbets', 0);
        if ($this->getInput('splitGobbets') == 1) {
            $this->splitGobbets($gobbets);
        } else {
            $this->mergeGobbets($gobbets);
        };
        if ($this->getInput('agenda') == 1) {
            $articles = $html->find('._articles', 0);
            if ($articles != null) {
                $this->collectArticles($articles);
            }
        }
        if ($this->getInput('quote') == 1) {
            $quote = $html->find('._quote-container', 0);
            $this->addQuote($quote);
        }
    }

    private function splitGobbets($gobbets)
    {
        $today = new Datetime();
        $today->setTime(0, 0, 0, 0);
        $limit = $this->getInput('limit');
        foreach ($gobbets->find('._gobbet') as $gobbet) {
            $title = $gobbet->plaintext;
            $match = preg_match('/[\.,]/', $title, $matches, PREG_OFFSET_CAPTURE);
            if ($match > 0) {
                $point = $matches[0][1];
                $title = mb_substr($title, 0, $point);
            }
            if ($limit && mb_strlen($title) > $limit) {
                $title = mb_substr($title, 0, $limit) . '...';
            }
            $item = [
                'uri' => self::URI,
                'title' => $title,
                'content' => $gobbet->innertext,
                'timestamp' => $today->format('U'),
                'uid' => md5($gobbet->plaintext)
            ];
            $this->items[] = $item;
        }
    }

    private function mergeGobbets($gobbets)
    {
        $today = new Datetime();
        $today->setTime(0, 0, 0, 0);
        $contents = '';
        foreach ($gobbets->find('._gobbet') as $gobbet) {
            $contents .= "<p>{$gobbet->innertext}";
        }
        $this->items[] = [
            'uri' => self::URI,
            'title' => 'World in brief at ' . $today->format('Y.m.d'),
            'content' => $contents,
            'timestamp' => $today->format('U'),
            'uid' => 'world-in-brief-' . $today->format('U')
        ];
    }

    private function collectArticles($articles)
    {
        $i = 0;
        $today = new Datetime();
        $today->setTime(0, 0, 0, 0);
        foreach ($articles->find('._article') as $article) {
            $title = $article->find('._headline', 0)->plaintext;
            $image = $article->find('._main-image', 0);
            $content = $article->find('._content', 0);

            $res_content = '';
            if ($image != null && $this->getInput('agendaPictures') == 1) {
                $img = $image->find('img', 0);
                $res_content .= '<img src="' . $img->src . '" />';
            }
            $res_content .= $content->innertext;
            $this->items[] = [
                'uri' => self::URI,
                'title' => $title,
                'content' => $res_content,
                'timestamp' => $today->format('U'),
                'uid' => 'story-' . $today->format('U') . "{$i}",
            ];
            $i++;
        }
    }

    private function addQuote($quote)
    {
        $today = new Datetime();
        $today->setTime(0, 0, 0, 0);
        $this->items[] = [
            'uri' => self::URI,
            'title' => 'Quote of the day ' . $today->format('Y.m.d'),
            'content' => $quote->innertext,
            'timestamp' => $today->format('U'),
            'uid' => 'quote-' . $today->format('U')
        ];
    }
}
