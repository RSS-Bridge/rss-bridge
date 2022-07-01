<?php

class ModelKarteiBridge extends BridgeAbstract
{
    const NAME = 'model-kartei.de';
    const URI = 'https://www.model-kartei.de/';
    const DESCRIPTION = 'Get the public comp card gallery';
    const MAINTAINER = 'fulmeek';
    const PARAMETERS = [[
        'model_id' => [
            'name'          => 'Model ID',
            'required' => true,
            'exampleValue'  => '614931'
        ]
    ]];

    const LIMIT_ITEMS = 10;

    private $feedName = '';

    public function collectData()
    {
        $model_id = preg_replace('/[^0-9]/', '', $this->getInput('model_id'));
        if (empty($model_id)) {
            returnServerError('Invalid model ID');
        }

        $html = getSimpleHTMLDOM(self::URI . 'sedcards/model/' . $model_id . '/');

        $objTitle = $html->find('.sTitle', 0);
        if ($objTitle) {
            $this->feedName = $objTitle->plaintext;
        }

        $itemlist = $html->find('#photoList .photoPreview');
        if (!$itemlist) {
            returnServerError('No gallery');
        }

        foreach ($itemlist as $idx => $element) {
            if ($idx >= self::LIMIT_ITEMS) {
                break;
            }

            $item = [];

            $title      = $element->title;
            $date       = $element->{'data-date'};
            $author     = $this->feedName;
            $text       = '';

            $objImage   = $element->find('a.photoLink img', 0);
            $objLink    = $element->find('a.photoLink', 0);

            if ($objLink) {
                $page = getSimpleHTMLDOMCached($objLink->href);

                if (empty($title)) {
                    $objTitle = $page->find('.p-title', 0);
                    if ($objTitle) {
                        $title = $objTitle->plaintext;
                    }
                }
                if (empty($date)) {
                    $objDate = $page->find('.cameraDetails .date', 0);
                    if ($objDate) {
                        $date = strtotime($objDate->parent()->plaintext);
                    }
                }
                if (empty($author)) {
                    $objAuthor = $page->find('.p-publisher a', 0);
                    if ($objAuthor) {
                        $author = $objAuthor->plaintext;
                    }
                }

                $objFullImage = $page->find('img#gofullscreen', 0);
                if ($objFullImage) {
                    $objImage = $objFullImage;
                }

                $objText = $page->find('.p-desc', 0);
                if ($objText) {
                    $text = $objText->plaintext;
                }
            }

            $item['title']      = $title;
            $item['timestamp']  = $date;
            $item['author']     = $author;

            if ($objImage) {
                $item['content'] = '<img src="' . $objImage->src . '"/>';
            }
            if ($objLink) {
                $item['uri'] = $objLink->href;
                if (!empty($item['content'])) {
                    $item['content'] = '<a href="' . $objLink->href . '" target="_blank">' . $item['content'] . '</a>';
                }
            } else {
                $item['uri'] = 'urn:sha1:' . hash('sha1', $item['content']);
            }
            if (!empty($text)) {
                $item['content'] = '<p>' . $text . '</p>' . $item['content'];
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName . ' - ' . self::NAME;
        }
        return parent::getName();
    }
}
