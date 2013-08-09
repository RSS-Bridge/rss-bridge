<?php
/**
 * RssBridgeGoogleMostRecent
 * Search Google for most recent pages regarding a specific topic.
 * Returns the 100 most recent links in results in past year, sorting by date (most recent first).
 * Example:
 * http://www.google.com/search?q=sebsauvage&num=100&complete=0&tbs=qdr:y,sbd:1
 *    complete=0&num=100 : get 100 results
 *    qdr:y : in past year
 *    sbd:1 : sort by date (will only work if qdr: is specified)
 *
 * @name Google search
 * @description Returns most recent results from Google search.
 * @use1(q="keyword search")
 */
 
class RssBridgeGoogleSearch extends RssBridgeAbstractClass
{
    protected $bridgeName = 'Google search';
    protected $bridgeURI = 'http://google.com';
    protected $bridgeDescription = 'Returns most recent results from Google search.';
    protected $cacheDuration = 30; // 30 minutes, otherwise you could get banned by Google, or stumblr upon their captcha.
    protected function collectData($request) {
        $html = '';
        if (isset($request['q'])) {   /* keyword search mode */
            $html = file_get_html('http://www.google.com/search?q='.urlencode($request['q']).'&num=100&complete=0&tbs=qdr:y,sbd:1') or $this->returnError(404, 'no results for this query.');
        } else {
            $this->returnError(400, 'You must specify a keyword (?q=...).');
        }
        $this->items = Array();
        foreach($html->find('div[id=ires]',0)->find('li[class=g]') as $element) {
            $item['uri'] = $element->find('a[href]',0)->href;
            $item['title'] = $element->find('h3',0)->plaintext;
            $item['content'] = $element->find('span[class=st]',0)->plaintext;
            $this->items[] = $item;
        }
    }
} 

$bridge = new RssBridgeGoogleSearch();
$bridge->process();