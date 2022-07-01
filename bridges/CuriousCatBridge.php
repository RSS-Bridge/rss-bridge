<?php

class CuriousCatBridge extends BridgeAbstract
{
    const NAME = 'Curious Cat Bridge';
    const URI = 'https://curiouscat.me';
    const DESCRIPTION = 'Returns list of newest questions and answers for a user profile';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [[
        'username' => [
            'name' => 'Username',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'koethekoethe',
        ]
    ]];

    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $url = self::URI . '/api/v2/profile?username=' . urlencode($this->getInput('username'));

        $apiJson = getContents($url);

        $apiData = json_decode($apiJson, true);

        foreach ($apiData['posts'] as $post) {
            $item = [];

            $item['author'] = 'Anonymous';

            if ($post['senderData']['id'] !== false) {
                $item['author'] = $post['senderData']['username'];
            }

            $item['uri'] = $this->getURI() . '/post/' . $post['id'];
            $item['title'] = $this->ellipsisTitle($post['comment']);

            $item['content'] = $this->processContent($post);
            $item['timestamp'] = $post['timestamp'];

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('username'))) {
            return self::URI . '/' . $this->getInput('username');
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('username'))) {
            return $this->getInput('username') . ' - Curious Cat';
        }

        return parent::getName();
    }

    private function processContent($post)
    {
        $author = 'Anonymous';

        if ($post['senderData']['id'] !== false) {
            $authorUrl = self::URI . '/' . $post['senderData']['username'];

            $author = <<<EOD
<a href="{$authorUrl}">{$post['senderData']['username']}</a>
EOD;
        }

        $question = $this->formatUrls($post['comment']);
        $answer = $this->formatUrls($post['reply']);

        $content = <<<EOD
<p>{$author} asked:</p>
<blockquote>{$question}</blockquote><br/>
<p>{$post['addresseeData']['username']} answered:</p>
<blockquote>{$answer}</blockquote>
EOD;

        return $content;
    }

    private function ellipsisTitle($text)
    {
        $length = 150;

        if (strlen($text) > $length) {
            $text = explode('<br>', wordwrap($text, $length, '<br>'));
            return $text[0] . '...';
        }

        return $text;
    }

    private function formatUrls($content)
    {
        return preg_replace(
            '/(http[s]{0,1}\:\/\/[a-zA-Z0-9.\/\?\&=\-_]{4,})/ims',
            '<a target="_blank" href="$1" target="_blank">$1</a> ',
            $content
        );
    }
}
