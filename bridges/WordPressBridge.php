<?php
/**
* RssBridgeWordpress
* Returns the newest articles
*
* @name Wordpress Bridge
* @description Returns the newest articles of a blog hosted on wordpress
* @use1(s="subdomain", f="folder")
*/
class WordpressBridge extends BridgeAbstract{

    private $subdomain;
	private $folder;

    public function collectData(array $param){
		$this->processParams($param);

		if (!$this->hasSubdomain()) {
			$this->returnError('You must specify a subdomain', 400);
		}

		$html = file_get_html($this->getSiteURI()) or $this->returnError("Could not request {$this->getSiteURI()}.", 404);

		foreach($html->find('.post') as $article) {
			$item = new \Item();

			$uri = $article->find('a[rel=bookmark]',0)->href;
			$item->uri = $uri;
			$item->title = $article->find('h2',0)->innertext;
			$item->content = $article->find('.entry',0)->innertext;
			preg_match('/\d{4}\/\d{2}\/\d{2}/', $uri, $matches);
			$date = new \DateTime($matches[0]);
			$item->timestamp = $date->format('U');
			$this->items[] = $item;
		}
    }

    public function getName(){
        return 'Wordpress.com Bridge';
    }

    public function getURI(){
        return 'http://%s.wordpress.com/%s';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }

	private function getSiteURI(){
		return sprintf($this->getURI(), $this->subdomain, $this->folder);
	}

	private function hasSubdomain(){
		if (empty($this->subdomain)){
			return false;
		}
		return true;
	}

	private function processParams($param){
		$this->subdomain = $param['s'];
		$this->folder = $param['f'];
	}

}
