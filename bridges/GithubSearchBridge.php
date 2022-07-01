<?php

class GithubSearchBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting';
    const NAME = 'Github Repositories Search';
    const URI = 'https://github.com/';
    const CACHE_TIMEOUT = 600; // 10min
    const DESCRIPTION = 'Returns a specified repositories search (sorted by recently updated)';
    const PARAMETERS = [ [
        's' => [
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'rss-bridge',
            'name' => 'Search query'
        ]
    ]];

    public function collectData()
    {
        $params = ['utf8' => '✓',
                                        'q' => urlencode($this->getInput('s')),
                                        's' => 'updated',
                                        'o' => 'desc',
                                        'type' => 'Repositories'];
        $url = self::URI . 'search?' . http_build_query($params);

        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('li.repo-list-item') as $element) {
            $item = [];

            $uri = $element->find('.f4 a', 0)->href;
            $uri = substr(self::URI, 0, -1) . $uri;
            $item['uri'] = $uri;

            $title = $element->find('.f4', 0)->plaintext;
            $item['title'] = $title;

            // Description
            if (count($element->find('p.mb-1')) != 0) {
                $content = $element->find('p.mb-1', 0)->innertext;
            } else {
                $content = 'No description';
            }

            // Tags
            $content = $content . '<br />';
            $tags = $element->find('a.topic-tag');
            $tags_array = [];
            if (count($tags) != 0) {
                $content = $content . 'Tags : ';
                foreach ($tags as $tag_element) {
                    $tag_link = 'https://github.com' . $tag_element->href;
                    $tag_name = trim($tag_element->innertext);
                    $content = $content . '<a href="' . $tag_link . '">' . $tag_name . '</a> ';
                    array_push($tags_array, $tag_element->innertext);
                }
            }

            $item['categories'] = $tags_array;
            $item['content'] = $content;
            $date = $element->find('relative-time', 0)->datetime;
            $item['timestamp'] = strtotime($date);

            $this->items[] = $item;
        }
    }
}
