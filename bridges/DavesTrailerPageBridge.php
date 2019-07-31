<?php
class DavesTrailerPageBridge extends BridgeAbstract {
    const MAINTAINER = 'johnnygroovy';
    const NAME = 'Daves Trailer Page Bridge';
    const URI = 'https://www.davestrailerpage.co.uk/';
    const DESCRIPTION = 'Last trailers in HD thanks to Dave.';

    public function collectData(){
	$html = '';
	$html = getSimpleHTMLDOM(static::URI)
	or returnClientError('No results for this query.');

	foreach ($html->find('tr[!align]') as $tr) { // traite tous les "tr" (pas centrés) un par un
	    $item = array();

	    // title
	    $item['title'] = $tr->find('td', 0)->find('b', 0)->plaintext;
	    
	    // content
	    $item['content'] = $tr->find('ul',1);
	    
	    // uri
	    $item['uri'] = $tr->find('a', 3)->getAttribute('href');
	    
	    $this->items[] = $item;
	}
    }
}
