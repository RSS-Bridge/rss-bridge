<?php

class ErowallBridge extends BridgeAbstract
{
    const NAME = 'Erowall.com Bridge';
    const URI = 'https://www.erowall.com/';
    const DESCRIPTION = 'Latest wallpapers from erowall.com';
    const MAINTAINER = 'kurz.junge';

    const PARAMETERS = [
        'global' => [
            'count' => [
                'type' => 'number',
                'name' => 'Count',
                'title' => 'How many wallpapers to fetch',
                'defaultValue' => 16
            ]
        ],
        'By tag' => [
            'tag' => [
                'type' => 'text',
                'name' => 'tag',
                'title' => 'Filter results by tag (e.g. playboy)',
                'required' => true
            ]
        ],
        'Latest' => [],
        'Most viewed' => [],
        'Most downloaded' => []
    ];

    public function collectData()
    {
        $requestedCount = $this->getInput('count');
        $count = 0;

        while ($count < $requestedCount) {
            # Indexing from 1
            $videosURL = $this->getPagedURI($count / 16 + 1);

            $website = getSimpleHTMLDOMCached($videosURL);
            $nodes = $website->find('.wpmini');

            foreach ($nodes as $wpmini) {
                $n = $wpmini->find('a', 0);

                # The href has format "/w/1234/" so we just remove all non-numeric
                $uid = preg_replace('/[^0-9]/', '', $n->href);
                $imageURL = self::URI . "/wallpapers/original/$uid.jpg";

                $item = [
                    'title' => "Wallpaper $uid",
                    'uri' => self::URI . $n->href,
                    'uid' => "$uid",
                    'enclosures' => [ $imageURL ],
                    'content' => "<img src=\"$imageURL\"/>"
                ];

                $tags = basename($n->title, ' wallpaper');
                $item['categories'] = array_map(
                    'ucwords',
                    explode(',', $tags)
                );

                $this->items[] = $item;
                $count++;

                if ($count >= $requestedCount) {
                    break;
                }
            }

            # In case that current page has less than 16 wallpapers, it is the
            # last page and we don't iterate further
            if (count($nodes) < 16) {
                break;
            }
        }
    }


    private function getPagedURI($pgnum)
    {
        return $this->getURI() . "/page/$pgnum";
    }

    public function getURI()
    {
        $ret = self::URI;
        switch ($this->queriedContext) {
            case 'Most viewed':
                $ret .= 'views/';
                break;
            case 'Most downloaded':
                $ret .= 'down/';
                break;
            case 'Latest':
                $ret .= 'dat/';
                break;
            default:
                $tag = $this->getInput('tag') ?? '';
                $ret .= 'teg/' . str_replace(' ', '+', $tag);
        }

        return $ret;
    }

    public function getName()
    {
        $count = $this->getInput('count');
        $ret = 'Erowall ';
        switch ($this->queriedContext) {
            case 'Most viewed':
            case 'Most downloaded':
            case 'Latest':
                $ret .= $count . ' ' . strtolower($this->queriedContext);
                break;
            case 'By tag':
                $tag = $this->getInput('tag');
                $ret .= "$count latest " . $tag;
                break;
            default:
        }

        return $ret . ' wallpapers';
    }
}
