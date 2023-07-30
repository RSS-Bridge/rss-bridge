<?php

class DoujinStyleBridge extends BridgeAbstract
{
    const NAME = 'DoujinStyle Bridge';
    const URI = 'https://doujinstyle.com/';
    const DESCRIPTION = 'Returns submissions from DoujinStyle';
    const MAINTAINER = 'mrtnvgr';

    // TODO: "Games" support

    const PARAMETERS = [
        'Most recent submissions' => [],
        'Randomly selected items' => [],
        'From search results' => [
            'query' => [
                'name' => 'Search query',
                'required' => true,
                'exampleValue' => 'FELT',
            ],
            'flac' => [
                'name' => 'Include FLAC',
                'type' => 'checkbox',
                'defaultValue' => false,
            ],
            'mp3' => [
                'name' => 'Include MP3',
                'type' => 'checkbox',
                'defaultValue' => false,
            ],
            'tta' => [
                'name' => 'Include TTA',
                'type' => 'checkbox',
                'defaultValue' => false,
            ],
            'opus' => [
                'name' => 'Include Opus',
                'type' => 'checkbox',
                'defaultValue' => false,
            ],
            'ogg' => [
                'name' => 'Include OGG',
                'type' => 'checkbox',
                'defaultValue' => false,
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $html = defaultLinkTo($html, $this->getURI());

        $submissions = $html->find('.gridBox .gridDetails');
        foreach ($submissions as $submission) {
            $item = [];

            $item['uri'] = $submission->find('a', 0)->href;

            $content = getSimpleHTMLDOM($item['uri']);
            $content = defaultLinkTo($content, $this->getURI());

            $title = $content->find('h2', 0)->plaintext;

            $cover = $content->find('#imgClick a', 0);
            if (is_null($cover)) {
                $cover = $content->find('.coverWrap', 0)->src;
            } else {
                $cover = $cover->href;
            }

            $item['content'] = "<img src='$cover'/>";

            $keys = [];
            foreach ($content->find('.pageWrap .pageSpan1') as $key) {
                $keys[] = $key->plaintext;
            }

            $values = $content->find('.pageWrap .pageSpan2');
            $metadata = array_combine($keys, $values);

            $format = 'Unknown';

            foreach ($metadata as $key => $value) {
                switch ($key) {
                    case 'Artist':
                        $artist = $value->find('a', 0)->plaintext;
                        $item['title'] = "$artist - $title";
                        $item['content'] .= "<br>Artist: $artist";
                        break;
                    case 'Tags:':
                        $item['categories'] = [];
                        foreach ($value->find('a') as $tag) {
                            $tag = str_replace('&#45;', '-', $tag->plaintext);
                            $item['categories'][] = $tag;
                        }

                        $item['content'] .= '<br>Tags: ' . join(', ', $item['categories']);
                        break;
                    case 'Format:':
                        $item['content'] .= "<br>Format: $value->plaintext";
                        break;
                    case 'Date Added:':
                        $item['timestamp'] = $value->plaintext;
                        break;
                    case 'Provided By:':
                        $item['author'] = $value->find('a', 0)->plaintext;
                        break;
                }
            }

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        $url = self::URI;

        switch ($this->queriedContext) {
            case 'From search results':
                $url .= '?p=search&type=blanket';
                $url .= '&result=' . $this->getInput('query');

                if ($this->getInput('flac') == 1) {
                    $url .= '&format0=on';
                }
                if ($this->getInput('mp3') == 1) {
                    $url .= '&format1=on';
                }
                if ($this->getInput('tta') == 1) {
                    $url .= '&format2=on';
                }
                if ($this->getInput('opus') == 1) {
                    $url .= '&format3=on';
                }
                if ($this->getInput('ogg') == 1) {
                    $url .= '&format4=on';
                }
                break;
            case 'Randomly selected items':
                $url .= '?p=random';
                break;
        }

        return $url;
    }
}
