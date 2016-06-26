<?php
class WikipediaDEBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "cnlpete";
		$this->name = "Wikipedia DE Today's Featured Article...";
		$this->uri = "https://de.wikipedia.org/";
		$this->description = "Returns the highlighted en.wikipedia.org article.";
		$this->update = "2015-11-04";

	}

    public function collectData(array $param){
        $html = '';
        $host = 'http://de.wikipedia.org';
        // If you want HTTPS access instead, uncomment the following line:
        //$host = 'https://de.wikipedia.org';
        $link = '/wiki/Wikipedia:Hauptseite';

        $html = $this->file_get_html($host.$link) or $this->returnError('Could not request Wikipedia DE.', 404);

        $element = $html->find('div[id=mf-tfa]', 0);
        $element->find('div', -1)->outertext = '';

        $item = new \Item();
        $item->uri = $host.$element->find('p', 0)->find('a', 0)->href;
        $item->title = $element->find('p',0)->find('a',0)->title;

        $html2 = $this->file_get_html($item->uri) or $this->returnError('Could not request Wikipedia DE '.$item->title.'.', 404);
        $element2 = $html2->find('div[id=mw-content-text]', 0);
        $item->content = str_replace('href="/', 'href="'.$host.'/', $element2->innertext);

        $this->items[] = $item;
    }

    public function getName(){
        return 'Wikipedia DE "Today\'s Featured Article"';
    }

    public function getURI(){
        return 'https://de.wikipedia.org/wiki/Wikipedia:Hauptseite';
    }

    public function getCacheDuration(){
        return 3600*8; // 8 hours
    }
}
