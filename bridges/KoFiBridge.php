<?php

class KoFiBridge extends BridgeAbstract
{
    const MAINTAINER = 'walkero';
    const NAME = 'Ko-Fi Bridge';
    const URI = 'https://ko-fi.com';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns the newest articles.';
    const FEED_URI = 'https://ko-fi.com/Feed/PersonalFeed?pageIndex=0&pageId=';
    const PARAMETERS = [[
        'pageId' => [
            'name' => 'Page ID',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'walkero',
        ]
    ]];

    public function collectData()
    {
        $limit = 0;
        $html = getSimpleHTMLDOM(self::FEED_URI . $this->getPageId());
        foreach ($html->find('div.feeditem-unit') as $element) {
            if ($limit < 10) {
                $titleWrapper = $element->find('div.content-link-text');
                if (isset($titleWrapper[0])) {
                    $item = [];
                    $item['title'] = $element->find('div.content-link-text div')[0]->plaintext;
                    $uri = $element->find('div.content-link-text div')[2]->find('a')[0]->onclick;
                    $uri = trim(str_replace('window.location =', '', $uri));
                    $uri = trim(str_replace('&#39;', '', $uri));
                    $uri = trim(str_replace(';', '', $uri));
                    $item['uri'] = self::URI . $uri;

                    if (isset($element->find('div.fi-post-item-large div.content-link-post img')[0])) {
                        $item['enclosures'][] = $element->find('div.fi-post-item-large div.content-link-post img')[0]->src;
                    }

                    $html = getSimpleHTMLDOM($item['uri']);
                    $feedItemTime = $html->find('div.feeditem-time', 0);
                    $feedItemTime->find('span', 0)->remove();
                    $feedItemTime->find('div', 0)->remove();
                    $item['timestamp'] = strtotime(trim($feedItemTime->plaintext));
                    $item['content'] = $this->getFullContent($html);
                    $html->clear();

                    $this->items[] = $item;
                    $limit++;
                }
            }
        }
        $html->clear();
    }

    private function getFullContent($html)
    {
        foreach ($html->find('script[type="text/javascript"]') as $script) {
            if (!empty($script->innertext)) {
                if (strpos($script->innertext, 'shadowDom.innerHTML += \'') !== false) {
                    preg_match_all('/d\N+/i', $script->innertext, $aMatches);
                    foreach ($aMatches[0] as $match) {
                        if (strpos($match, 'article-body') !== false) {
                            break;
                        }
                    }
                    $fullPostHtml = str_get_html(mb_substr($match, 21, -3));
                    // Get the first paragraph
                    return mb_substr($fullPostHtml->innertext, 0, mb_strpos($fullPostHtml->innertext, '</p>') + 4);
                }
            }
        }
    }

    private function getPageId()
    {
        $html = getSimpleHTMLDOM(self::URI . '/' . $this->getInput('pageId'));
        $reportUrl = $html->find('div.modal-dialog div.mb a.btn')[1]->href;
        $html->clear();
        return substr($reportUrl, strpos($reportUrl, '=') + 1);
    }
}
