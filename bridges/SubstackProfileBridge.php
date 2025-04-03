<?php

class SubstackProfileBridge extends BridgeAbstract
{
    const NAME = 'Substack Profile';
    const MAINTAINER = 'phantop';
    const URI = 'https://substack.com/';
    const DESCRIPTION = 'Returns posts from profiles on Substack';
    const PARAMETERS = [[
        'profile' => [
            'name' => 'Profile name to use',
            'exampleValue' => 'taliabhatt',
        ],
    ]];

    private $name;
    private $icon;
    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI());
        preg_match('/<script>window\._preloads\s*= JSON\.parse\("(.+?)"\)\s*<\/script>/', $html, $preg);
        $json = stripcslashes($preg[1]);
        $profile = json_decode($json, true)['profile'];

        $this->name = $profile['name'];
        $this->icon = $profile['photo_url'];

        $id = $profile['id'];
        $json = getContents(parent::getURI() . "api/v1/reader/feed/profile/$id");
        foreach (json_decode($json, true)['items'] as $element) {
            $this->items[] = $this->processAttachment($element);
        }
    }

    private function processAttachment(array $element)
    {
        $item = [];
        switch ($element['type']) {
            case 'comment':
                $element = $element['comment'];
                $item['author'] = $element['name'] ?? $element['user']['name'];
                $item['content'] = '';
                if (isset($element['body_json'])) {
                    $item['content'] = $this->processBodyJson($element['body_json']);
                }
                $item['timestamp'] = $element['date'];
                $item['title'] = 'Comment by ' . $item['author'];
                $item['uri'] = $this->getURI() . '/note/c-' . $element['id'];
                break;
            case 'post':
                $item['content'] = $element['postSelection']['text'] ?? '';
                $element = $element['post'];
                $item['author'] = $element['publishedBylines'][0]['name'];
                $item['content'] .= $this->fetchPost($element['id']);
                $item['timestamp'] = $element['post_date'];
                $item['title'] = $element['title'];
                $item['uri'] = parent::getURI() . 'home/post/p-' . $element['id'];
                break;
            case 'link':
                $element = $element['linkMetadata'];
                $item['author'] = $element['host'];
                $item['content'] = $element['description'];
                $item['title'] = $element['title'];
                $item['uri'] = $element['url'];
                break;
            case 'image':
                $item['uri'] = $element['imageUrl'];
                break;
            default:
                throw new Exception('Invalid Substack entry type: ' . $element['type']);
        }

        $item['enclosures'] = [
            $element['audio_items'][0]['audio_url'] ?? null,
            $element['audio_items'][1]['audio_url'] ?? null,
            $element['cover_image'] ?? null,
            $element['image'] ?? null,
            $element['imageUrl'] ?? null,
        ];

        $item['categories'] = array_map(fn($tag) => $tag['name'], $element['postTags'] ?? []);
        $item['comments'] = $item['uri'] . '/restacks/notes';

        if (isset($element['attachments'])) {
            foreach ($element['attachments'] as $attachment) {
                $attachment = $this->processAttachment($attachment);
                $item['categories'] = array_merge($item['categories'], $attachment['categories']);
                $item['enclosures'] = array_merge($item['enclosures'], $attachment['enclosures']);
                if (isset($attachment['title'])) { // Nothing to quote for images
                    $item['content'] .= $this->quoteAttachment($attachment);
                }
            }
        }

        return $item;
    }

    private function fetchPost(string $id)
    {
        $json = getContents(parent::getURI() . "api/v1/posts/by-id/$id");
        $json = json_decode($json, true)['post'];
        $html = str_get_html($json['body_html']);
        $body = $html->root;
        $block = $html->createElement('div');
        $block->appendChild($html->createElement('hr'));
        $block->appendChild($html->createElement('h4', 'Full text:'));
        $block->appendChild($body);
        return $block->innertext();
    }

    private function quoteAttachment(array $attachment)
    {
        $html = new simple_html_dom();
        $body = $html->createElement('div');
        $body->appendChild($html->createElement('hr'));
        $link = $html->createElement('a');
        $link->href = $attachment['uri'];
        $link->appendChild($html->createElement('h3', $attachment['title']));
        $body->appendChild($link);
        if ($attachment['content'] != '') {
            $body->appendChild($html->createElement('h4', 'Qouting ' . $attachment['author'] . ':'));
            $body->appendChild($html->createElement('blockquote', $attachment['content']));
        }
        return $body->innertext();
    }

    private function processBodyJson(array $json)
    {
        $html = new simple_html_dom();
        $body = $html->createElement('div');
        foreach ($json['content'] as $block) {
            if (isset($block['content'])) {
                $content = $this->processBodyJson($block);
            }
            switch ($block['type']) {
                case 'blockquote':
                    $content->tag = 'blockquote';
                    $body->appendChild($content);
                    break;
                case 'paragraph':
                    $content->tag = 'p';
                    $body->appendChild($content);
                    break;
                case 'text':
                    $text = $html->createTextNode($block['text']);
                    if (isset($block['marks'])) {
                        foreach ($block['marks'] as $mark) {
                            switch ($mark['type']) {
                                case 'bold':
                                    $marked = $html->createElement('strong');
                                    $marked->appendChild($text);
                                    $text = $marked;
                                    break;
                                case 'italic':
                                    $marked = $html->createElement('em');
                                    $marked->appendChild($text);
                                    $text = $marked;
                                    break;
                                case 'link':
                                    $marked = $html->createElement('a');
                                    $marked->href = $mark['attrs']['href'];
                                    $marked->appendChild($text);
                                    $text = $marked;
                                    break;
                                default:
                                    throw new Exception('Invalid text mark type: ' . $mark['type']);
                            }
                        }
                    }
                    $body->appendChild($text);
                    break;
                case 'substack_mention':
                    $link = $html->createElement('a');
                    $link->href = parent::getURI() . 'profile/' . $block['attrs']['id'];
                    $link->appendChild($html->createTextNode($block['attrs']['label']));
                    $body->appendChild($link);
                    break;
                default:
                    throw new Exception('Invalid body type: ' . $block['type']);
            }
        }
        return $body;
    }

    public function getName()
    {
        $name = parent::getName();
        if (isset($this->name)) {
            $name .= " - $this->name";
        }
        return $name;
    }

    public function getIcon()
    {
        if (isset($this->icon)) {
            return $this->icon;
        }
        return parent::getIcon();
    }

    public function getURI()
    {
        if ($this->getInput('profile') != null) {
            return parent::getURI() . '@' . $this->getInput('profile');
        }
        return parent::getURI();
    }
}
