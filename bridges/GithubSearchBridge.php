<?php

class GithubSearchBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting, User123698745';
    const NAME = 'Github Repositories Search';
    const BASE_URI = 'https://github.com';
    const URI = self::BASE_URI . '/search';
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
        $html = getSimpleHTMLDOM(self::getURI());

        $resultElement = $html->find('[data-testid="results-list"]', 0);

        foreach ($resultElement->children as $element) {
            $titleElement = $element->find('.search-title', 0);
            $descriptionElement = $element->find('div > .search-match', 0);
            $topicElements = $element->find('a[href^="/topic"]');
            $languageElement = $element->find('li [aria-label$="language"]', 0);
            $dateElement = $element->find('li [title*=" "]', 0);

            $item = [];
            $item['uri'] = self::BASE_URI . $titleElement->find('a', 0)->href;
            $item['title'] = trim($titleElement->plaintext);
            $item['timestamp'] = strtotime($dateElement->attr['title']);

            $categories = [];

            // Description
            $content = '<p>';
            if (isset($descriptionElement)) {
                $content .= trim($descriptionElement->plaintext);
            } else {
                $content .= 'No description';
            }
            $content .= '</p>';

            // Topics
            if (count($topicElements) > 0) {
                $content .= '<p>';
                $content .= 'Topics: ';
                foreach ($topicElements as $topicElement) {
                    $topicLink = self::BASE_URI . $topicElement->href;
                    $topicTitle = trim($topicElement->plaintext);
                    $content .= '<a href="' . $topicLink . '">' . $topicTitle . '</a> ';
                    $categories[] = $topicTitle;
                }
                $content .= '</p>';
            }

            // Programming language
            if (isset($languageElement)) {
                $content .= '<p>';
                $content .= 'Language: ';
                $content .= trim($languageElement->plaintext);
                $content .= '</p>';
            }

            $item['content'] = $content;
            $item['categories'] = $categories;

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        $searchValue = $this->getInput('s');
        if (isset($searchValue)) {
            $params = [
                'q' => $searchValue,
                'type' => 'repositories',
                's' => 'updated',
                'o' => 'desc',
            ];
            return self::URI . '?' . http_build_query($params);
        }
        return self::URI;
    }
}
