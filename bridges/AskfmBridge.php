<?php

class AskfmBridge extends BridgeAbstract
{
    const MAINTAINER = 'az5he6ch, logmanoriginal';
    const NAME = 'Ask.fm Answers';
    const URI = 'https://ask.fm/';
    const CACHE_TIMEOUT = 300; //5 min
    const DESCRIPTION = 'Returns answers from an Ask.fm user';
    const PARAMETERS = [
        'Ask.fm username' => [
            'u' => [
                'name' => 'Username',
                'required' => true,
                'exampleValue'  => 'ApprovedAndReal'
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $html = defaultLinkTo($html, self::URI);

        foreach ($html->find('article.streamItem-answer') as $element) {
            $item = [];
            $item['uri'] = $element->find('a.streamItem_meta', 0)->href;
            $question = trim($element->find('header.streamItem_header', 0)->innertext);

            $item['title'] = trim(
                htmlspecialchars_decode(
                    $element->find('header.streamItem_header', 0)->plaintext,
                    ENT_QUOTES
                )
            );

            $item['timestamp'] = strtotime($element->find('time', 0)->datetime);

            $answer = trim($element->find('div.streamItem_content', 0)->innertext);

            // This probably should be cleaned up, especially for YouTube embeds
            if ($visual = $element->find('div.streamItem_visual', 0)) {
                $visual = $visual->innertext;
            }

            // Fix tracking links, also doesn't work
            foreach ($element->find('a') as $link) {
                if (strpos($link->href, 'l.ask.fm') !== false) {
                    $link->href = $link->plaintext;
                }
            }

            $item['content'] = '<p>' . $question
            . '</p><p>' . $answer
            . '</p><p>' . $visual . '</p>';

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            return self::NAME . ' : ' . $this->getInput('u');
        }

        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return self::URI . urlencode($this->getInput('u'));
        }

        return parent::getURI();
    }
}
