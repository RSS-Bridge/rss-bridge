<?php
define('GIPHY_LIMIT', 10);

class GiphyBridge extends BridgeAbstract{

	const MAINTAINER = "kraoc";
	const NAME = "Giphy Bridge";
	const URI = "http://giphy.com/";
	const CACHE_TIMEOUT = 300; //5min
	const DESCRIPTION = "Bridge for giphy.com";

    const PARAMETERS = array( array(
        's'=>array(
            'name'=>'search tag',
            'required'=>true
        ),
        'n'=>array(
            'name'=>'max number of returned items',
            'type'=>'number'
        )
    ));

	public function collectData(){
		$html = '';
        $base_url = 'http://giphy.com';
        $html = getSimpleHTMLDOM(self::URI.'/search/'.urlencode($this->getInput('s').'/'))
            or returnServerError('No results for this query.');

        $max = GIPHY_LIMIT;
        if ($this->getInput('n')) {
            $max = $this->getInput('n');
        }

        $limit = 0;
        $kw = urlencode($this->getInput('s'));
        foreach($html->find('div.hoverable-gif') as $entry) {
            if($limit < $max) {
                $node = $entry->first_child();
                $href = $node->getAttribute('href');

                $html2 = getSimpleHTMLDOM(self::URI . $href)
                    or returnServerError('No results for this query.');
                $figure = $html2->getElementByTagName('figure');
                $img = $figure->firstChild();
                $caption = $figure->lastChild();

                $item = array();
                $item['id'] = $img->getAttribute('data-gif_id');
                $item['uri'] = $img->getAttribute('data-bitly_gif_url');
                $item['username'] = 'Giphy - '.ucfirst($kw);
                $title = $caption->innertext();
                    $title = preg_replace('/\s+/', ' ',$title);
                    $title = str_replace('animated GIF', '', $title);
                    $title = str_replace($kw, '', $title);
                    $title = preg_replace('/\s+/', ' ',$title);
                    $title = trim($title);
                    if (strlen($title) <= 0) {
                        $title = $item['id'];
                    }
                $item['title'] = trim($title);
                $item['content'] =
                    '<a href="'.$item['uri'].'">'
                        .'<img src="'.$img->getAttribute('src').'" width="'.$img->getAttribute('data-original-width').'" height="'.$img->getAttribute('data-original-height').'" />'
                    .'</a>';

                $this->items[] = $item;
                $limit++;
            }
        }
	}
}
