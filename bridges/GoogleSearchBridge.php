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
* @use1(q="keyword")
*/
class GoogleSearchBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';

        if (isset($param['q'])) {   /* keyword search mode */
            $html = file_get_html('http://www.google.com/search?q=' . urlencode($param['q']) . '&num=100&complete=0&tbs=qdr:y,sbd:1') or $this->returnError('No results for this query.', 404);
        }
        else{
            $this->returnError('You must specify a keyword (?q=...).', 400);
        }

        $emIsRes = $html->find('div[id=ires]',0);
        if( !is_null($emIsRes) ){
            foreach($emIsRes->find('li[class=g]') as $element) {
                $item = new \Item();
                $item->uri = $element->find('a[href]',0)->href;
                $item->title = $element->find('h3',0)->plaintext;
                $item->content = $element->find('span[class=st]',0)->plaintext;
                $this->items[] = $item;
            }
        }
    }

    public function getName(){
        return 'Google search';
    }

    public function getURI(){
        return 'http://google.com';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}