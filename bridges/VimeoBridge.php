<?php

class VimeoBridge extends BridgeAbstract
{
    const NAME = 'Vimeo Bridge';
    const URI = 'https://vimeo.com/';
    const DESCRIPTION = 'Returns search results from Vimeo';
    const MAINTAINER = 'logmanoriginal';

    const PARAMETERS = [
        [
            'q' => [
                'name' => 'Search Query',
                'type' => 'text',
                'exampleValue' => 'birds',
                'required' => true
            ],
            'type' => [
                'name' => 'Show results for',
                'type' => 'list',
                'defaultValue' => 'Videos',
                'values' => [
                    'Videos' => 'search',
                    'On Demand' => 'search/ondemand',
                    'People' => 'search/people',
                    'Channels' => 'search/channels',
                    'Groups' => 'search/groups'
                ]
            ]
        ]
    ];

    public function getURI()
    {
        if (
            ($query = $this->getInput('q'))
            && ($type = $this->getInput('type'))
        ) {
            return self::URI . $type . '/sort:latest?q=' . $query;
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(
            $this->getURI(),
            $header = [],
            $opts = [],
            $lowercase = true,
            $forceTagsClosed = true,
            $target_charset = DEFAULT_TARGET_CHARSET,
            $stripRN = false, // We want to keep newline characters
            $defaultBRText = DEFAULT_BR_TEXT,
            $defaultSpanText = DEFAULT_SPAN_TEXT
        );

        $json = null; // Holds the JSON data

        /**
         * Search results are included as JSON formatted string inside a script
         * tag that has the variable 'vimeo.config'. The data is condensed into
         * a single line of code, so we can just search for the newline.
         *
         * Everything after "vimeo.config = _extend((vimeo.config || {}), " is
         * the JSON formatted string.
         */
        foreach ($html->find('script') as $script) {
            foreach (explode("\n", $script) as $line) {
                $line = trim($line);

                if (strpos($line, 'vimeo.config') !== 0) {
                    continue;
                }

                // 45 = strlen("vimeo.config = _extend((vimeo.config || {}), ");
                // 47 = 45 + 2, because we don't want the final ");"
                $json = json_decode(substr($line, 45, strlen($line) - 47));
            }
        }

        if (is_null($json)) {
            returnClientError('No results for this query!');
        }

        foreach ($json->api->initial_json->data as $element) {
            switch ($element->type) {
                case 'clip':
                    $this->addClip($element);
                    break;
                case 'ondemand':
                    $this->addOnDemand($element);
                    break;
                case 'people':
                    $this->addPeople($element);
                    break;
                case 'channel':
                    $this->addChannel($element);
                    break;
                case 'group':
                    $this->addGroup($element);
                    break;

                default:
                    returnServerError('Unknown type: ' . $element->type);
            }
        }
    }

    private function addClip($element)
    {
        $item = [];

        $item['uri'] = $element->clip->link;
        $item['title'] = $element->clip->name;
        $item['author'] = $element->clip->user->name;
        $item['timestamp'] = strtotime($element->clip->created_time);

        $item['enclosures'] = [
            end($element->clip->pictures->sizes)->link
        ];

        $item['content'] = "<img src={$item['enclosures'][0]} />";

        $this->items[] = $item;
    }

    private function addOnDemand($element)
    {
        $item = [];

        $item['uri'] = $element->ondemand->link;
        $item['title'] = $element->ondemand->name;

        // Only for films
        if (isset($element->ondemand->film)) {
            $item['timestamp'] = strtotime($element->ondemand->film->release_time);
        }

        $item['enclosures'] = [
            end($element->ondemand->pictures->sizes)->link
        ];

        $item['content'] = "<img src={$item['enclosures'][0]} />";

        $this->items[] = $item;
    }

    private function addPeople($element)
    {
        $item = [];

        $item['uri'] = $element->people->link;
        $item['title'] = $element->people->name;

        $item['enclosures'] = [
            end($element->people->pictures->sizes)->link
        ];

        $item['content'] = "<img src={$item['enclosures'][0]} />";

        $this->items[] = $item;
    }

    private function addChannel($element)
    {
        $item = [];

        $item['uri'] = $element->channel->link;
        $item['title'] = $element->channel->name;

        $item['enclosures'] = [
            end($element->channel->pictures->sizes)->link
        ];

        $item['content'] = "<img src={$item['enclosures'][0]} />";

        $this->items[] = $item;
    }

    private function addGroup($element)
    {
        $item = [];

        $item['uri'] = $element->group->link;
        $item['title'] = $element->group->name;

        $item['enclosures'] = [
            end($element->group->pictures->sizes)->link
        ];

        $item['content'] = "<img src={$item['enclosures'][0]} />";

        $this->items[] = $item;
    }
}
