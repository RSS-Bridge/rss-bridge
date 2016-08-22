<?php
class DailymotionBridge extends BridgeAbstract{

	private $request;

    public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Dailymotion Bridge";
		$this->uri = "https://www.dailymotion.com/";
		$this->description = "Returns the 5 newest videos by username/playlist or search";

        $this->parameters["By username"] = array(
          'u'=>array('name'=>'username')
        );

        $this->parameters["By playlist id"] = array(
          'p'=>array(
            'name'=>'playlist id',
            'type'=>'text')
        );

        $this->parameters["From search results"] = array(
          's'=>array('name'=>'Search keyword'),
          'pa'=>array(
            'name'=>'Page',
            'type'=>'number'
          )
        );
	}

    function getMetadata($id) {
      $metadata=array();
      $html2 = $this->getSimpleHTMLDOM('http://www.dailymotion.com/video/'.$id) or $this->returnServerError('Could not request Dailymotion.');
      $metadata['title'] = $html2->find('meta[property=og:title]', 0)->getAttribute('content');
      $metadata['timestamp'] = strtotime($html2->find('meta[property=video:release_date]', 0)->getAttribute('content') );
      $metadata['thumbnailUri'] = $html2->find('meta[property=og:image]', 0)->getAttribute('content');
      $metadata['uri'] = $html2->find('meta[property=og:url]', 0)->getAttribute('content');

      return $metadata;
    }

	public function collectData(array $param){
        	$html = '';
		$limit = 5;
		$count = 0;

		if (isset($param['u'])) {   // user timeline mode
			$this->request = $param['u'];
			$html = $this->getSimpleHTMLDOM('http://www.dailymotion.com/user/'.urlencode($this->request).'/1') or $this->returnServerError('Could not request Dailymotion.');
		}
		else if (isset($param['p'])) {    // playlist mode
			$this->request = strtok($param['p'], '_');
			$html = $this->getSimpleHTMLDOM('http://www.dailymotion.com/playlist/'.urlencode($this->request).'') or $this->returnServerError('Could not request Dailymotion.');
		}
		else if (isset($param['s'])) {   // search mode
			$this->request = $param['s']; $page = 1; if (isset($param['pa'])) $page = (int)preg_replace("/[^0-9]/",'', $param['pa']);
			$html = $this->getSimpleHTMLDOM('http://www.dailymotion.com/search/'.urlencode($this->request).'/'.$page.'') or $this->returnServerError('Could not request Dailymotion.');
		}
		else {
			$this->returnClientError('You must either specify a Dailymotion username (?u=...) or a playlist id (?p=...) or search (?s=...)');
		}

		foreach($html->find('div.media a.preview_link') as $element) {
			if($count < $limit) {
				$item = array();
				$item['id'] = str_replace('/video/', '', strtok($element->href, '_'));
				$metadata = $this->getMetadata($item['id']);
				$item['uri'] = $metadata['uri'];
				$item['title'] = $metadata['title'];
				$item['timestamp'] = $metadata['timestamp'];
				$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $metadata['thumbnailUri'] . '" /></a><br><a href="' . $item['uri'] . '">' . $item['title'] . '</a>';
				$this->items[] = $item;
				$count++;
			}
		}
	}

	public function getName(){
		return (!empty($this->request) ? $this->request .' - ' : '') .'Dailymotion Bridge';
	}

	public function getCacheDuration(){
		return 3600*3; // 3 hours
	}
}
