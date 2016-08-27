<?php
class DailymotionBridge extends BridgeAbstract{

        public $maintainer = "mitsukarenai";
        public $name = "Dailymotion Bridge";
        public $uri = "https://www.dailymotion.com/";
        public $description = "Returns the 5 newest videos by username/playlist or search";

        public $parameters = array (
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

    function getMetadata($id) {
        $metadata=array();
        $html2 = $this->getSimpleHTMLDOM('http://www.dailymotion.com/video/'.$id)
            or $this->returnServerError('Could not request Dailymotion.');
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

        $html = $this->getSimpleHTMLDOM($this->getURI())
            or $this->returnServerError('Could not request Dailymotion.');

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
        $param=$this->parameters[$this->queriedContext];
        switch($this->queriedContext){
        case 'By username':
            $specific=$param['u']['value'];
            break;
        case 'By playlist id':
            $specific=strtok($param['p']['value'], '_');
            break;
        case 'From search results':
            $specific=$param['s']['value'];
            break;
        }

        return $specific.' : Dailymotion Bridge';
    }

    public function getURI(){
        $param=$this->parameters[$this->queriedContext];
        switch($this->queriedContext){
        case 'By username':
            $uri='http://www.dailymotion.com/user/'
                .urlencode($param['u']['value']).'/1';
            break;
        case 'By playlist id':
            $uri='http://www.dailymotion.com/playlist/'
                .urlencode(strtok($param['p']['value'], '_'));
            break;
        case 'From search results':
            $uri='http://www.dailymotion.com/search/'
                .urlencode($param['s']['value']);
            if(isset($param['pa']['value'])){
                $uri.='/'.$param['pa']['value'];
            }
            break;
        }
        return $uri;
    }

    public function getCacheDuration(){
        return 3600*3; // 3 hours
    }
}
