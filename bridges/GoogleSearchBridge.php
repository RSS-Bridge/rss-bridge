<?php
/**
* Returns the 100 most recent links in results in past year, sorting by date (most recent first).
* Example:
* http://www.google.com/search?q=sebsauvage&num=100&complete=0&tbs=qdr:y,sbd:1
*    complete=0&num=100 : get 100 results
*    qdr:y : in past year
*    sbd:1 : sort by date (will only work if qdr: is specified)
*/
class GoogleSearchBridge extends BridgeAbstract{


	public $maintainer = "sebsauvage";
	public $name = "Google search";
	public $uri = "https://www.google.com/";
	public $description = "Returns most recent results from Google search.";

    public $parameters = array( array(
        'q'=>array(
            'name'=>"keyword",
            'required'=>true
        )
    ));


    public function collectData(){
        $html = '';

            $html = $this->getSimpleHTMLDOM('https://www.google.com/search?q=' . urlencode($this->getInput('q')) . '&num=100&complete=0&tbs=qdr:y,sbd:1') or $this->returnServerError('No results for this query.');

        $emIsRes = $html->find('div[id=ires]',0);
        if( !is_null($emIsRes) ){
            foreach($emIsRes->find('li[class=g]') as $element) {
                $item = array();

                // Extract direct URL from google href (eg. /url?q=...)
                $t = $element->find('a[href]',0)->href;
                $item['uri'] = ''.$t;
                parse_str(parse_url($t, PHP_URL_QUERY),$parameters);
                if (isset($parameters['q'])) { $item['uri'] = $parameters['q']; }
                $item['title'] = $element->find('h3',0)->plaintext;
                $item['content'] = $element->find('span[class=st]',0)->plaintext;
                $this->items[] = $item;
            }
        }
    }

    public function getName(){
        return $this->getInput('q') .' - Google search';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
