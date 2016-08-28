<?php
define('GIPHY_LIMIT', 10);

class GiphyBridge extends BridgeAbstract{

	public $maintainer = "kraoc";
	public $name = "Giphy Bridge";
	public $uri = "http://giphy.com/";
	public $description = "Bridge for giphy.com";

    public $parameters = array( array(
        's'=>array('name'=>'search tag'),
        'n'=>array(
            'name'=>'max number of returned items',
            'type'=>'number'
        )
    ));

	public function collectData(){
		$html = '';
        $base_url = 'http://giphy.com';
		if ($this->getInput('s')) {   /* keyword search mode */
			$html = $this->getSimpleHTMLDOM($base_url.'/search/'.urlencode($this->getInput('s').'/')) or $this->returnServerError('No results for this query.');
		}
		else {
			$this->returnClientError('You must specify a search worf (?s=...).');
		}

        $max = GIPHY_LIMIT;
        if ($this->getInput('n')) {
            $max = (integer) $this->getInput('n');
        }

        $limit = 0;
        $kw = urlencode($this->getInput('s'));
        foreach($html->find('div.hoverable-gif') as $entry) {
            if($limit < $max) {
                $node = $entry->first_child();
                $href = $node->getAttribute('href');

                $html2 = $this->getSimpleHTMLDOM($base_url . $href) or $this->returnServerError('No results for this query.');
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

	public function getCacheDuration(){
		return 300; // 5 minutes
	}
}
