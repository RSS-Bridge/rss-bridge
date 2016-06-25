<?php
class WikipediaENBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "gsurrel";
		$this->name = "Wikipedia EN 'Today's Featured Article...'";
		$this->uri = "https://en.wikipedia.org/";
		$this->description = "Returns the highlighted en.wikipedia.org article.";
		$this->update = "2014-05-25";

	}

    public function collectData(array $param){
        $html = '';
        $host = 'http://en.wikipedia.org';
        // If you want HTTPS access instead, uncomment the following line:
        //$host = 'https://en.wikipedia.org';
        $link = '/wiki/Main_Page';

        $html = $this->file_get_html($host.$link) or $this->returnError('Could not request Wikipedia EN.', 404);

		$element = $html->find('div[id=mp-tfa]', 0);
		// Clean the bottom of the featured article
		$element->find('div', -1)->outertext = '';
		$item = new \Item();
		$item->uri = $host.$element->find('p', 0)->find('a', 0)->href;
		$item->title = $element->find('p',0)->find('a',0)->title;
		$item->content = str_replace('href="/', 'href="'.$host.'/', $element->innertext);
		$this->items[] = $item;
    }

    public function getName(){
        return 'Wikipedia EN "Today\'s Featued Article"';
    }

    public function getURI(){
        return 'https://en.wikipedia.org/wiki/Main_Page';
    }

    public function getCacheDuration(){
        return 3600*4; // 4 hours
    }
}
