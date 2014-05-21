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
* @maintainer -
* @use1(q="keyword")
*/
class GoogleSearchBridge extends BridgeAbstract{

    private $request;
    
    public function collectData(array $param){
        $html = '';

        if (isset($param['q'])) {   /* keyword search mode */
            $this->request = $param['q'];
            $html = file_get_html('http://www.google.com/search?q=' . urlencode($this->request) . '&num=100&complete=0&tbs=qdr:y,sbd:1') or $this->returnError('No results for this query.', 404);
        }
        else{
            $this->returnError('You must specify a keyword (?q=...).', 400);
        }

        $emIsRes = $html->find('div[id=ires]',0);
        if( !is_null($emIsRes) ){
            foreach($emIsRes->find('li[class=g]') as $element) {
                $item = new Item();
                
                // Extract direct URL from google href (eg. /url?q=...)
                $t = $element->find('a[href]',0)->href;
                $item->uri = 'http://google.com'.$t;
                parse_str(parse_url($t, PHP_URL_QUERY),$parameters);
                if (isset($parameters['q'])) { $item->uri = $parameters['q']; }
                $item->title = $element->find('h3',0)->plaintext;
                $item->content = $element->find('span[class=st]',0)->plaintext;
                $this->items[] = $item;
            }
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Google search';
    }

    public function getURI(){
        return 'http://google.com';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
