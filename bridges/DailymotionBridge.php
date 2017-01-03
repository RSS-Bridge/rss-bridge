<?php
class DailymotionBridge extends BridgeAbstract{

        const MAINTAINER = "mitsukarenai";
        const NAME = "Dailymotion Bridge";
        const URI = "https://www.dailymotion.com/";
        const CACHE_TIMEOUT = 10800; // 3h
        const DESCRIPTION = "Returns the 5 newest videos by username/playlist or search";

        const PARAMETERS = array (
            'By username' => array(
                'u'=>array(
                    'name'=>'username',
                    'required'=>true
                )
            ),

            'By playlist id' => array(
                'p'=>array(
                    'name'=>'playlist id',
                    'required'=>true
                )
            ),

            'From search results' => array(
                's'=>array(
                    'name'=>'Search keyword',
                    'required'=>true
                ),
                'pa'=>array(
                    'name'=>'Page',
                    'type'=>'number'
                )
            )
        );

    protected function getMetadata($id) {
        $metadata=array();
        $html2 = getSimpleHTMLDOM(self::URI.'video/'.$id);
        if(!$html2){
            return $metadata;
        }

        $metadata['title'] = $html2->find('meta[property=og:title]', 0)->getAttribute('content');
        $metadata['timestamp'] = strtotime($html2->find('meta[property=video:release_date]', 0)->getAttribute('content') );
        $metadata['thumbnailUri'] = $html2->find('meta[property=og:image]', 0)->getAttribute('content');
        $metadata['uri'] = $html2->find('meta[property=og:url]', 0)->getAttribute('content');
        return $metadata;
    }

    public function collectData(){
        $html = '';
        $limit = 5;
        $count = 0;

        $html = getSimpleHTMLDOM($this->getURI())
            or returnServerError('Could not request Dailymotion.');

        foreach($html->find('div.media a.preview_link') as $element) {
            if($count < $limit) {
                $item = array();
                $item['id'] = str_replace('/video/', '', strtok($element->href, '_'));
                $metadata = $this->getMetadata($item['id']);
                if(empty($metadata)){
                    continue;
                }
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
        switch($this->queriedContext){
        case 'By username':
            $specific=$this->getInput('u');
            break;
        case 'By playlist id':
            $specific=strtok($this->getInput('p'), '_');
            break;
        case 'From search results':
            $specific=$this->getInput('s');
            break;
        default: return parent::getName();
        }

        return $specific.' : Dailymotion Bridge';
    }

    public function getURI(){
        $uri=self::URI;
        switch($this->queriedContext){
        case 'By username':
            $uri.='user/'
                .urlencode($this->getInput('u')).'/1';
            break;
        case 'By playlist id':
            $uri.='playlist/'
                .urlencode(strtok($this->getInput('p'), '_'));
            break;
        case 'From search results':
            $uri.='search/'
                .urlencode($this->getInput('s'));
            if($this->getInput('pa')){
                $uri.='/'.$this->getInput('pa');
            }
            break;
        }
        return $uri;
    }
}
