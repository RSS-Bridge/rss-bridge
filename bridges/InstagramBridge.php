<?php
/**
 * RssBridgeInstagram
 * Returns the newest photos
 *
 * @name Instagram Bridge
 * @description Returns the newest images
 * @use1(u="username")
 */
class InstagramBridge extends BridgeAbstract{
    
    private $request;
    
    public function collectData(array $param){
        $html = '';
        if (isset($param['u'])) {   /* user timeline mode */
            $this->request = $param['u'];
            $text = file_get_contents('http://instagram.com/'.urlencode($this->request)) or $this->returnError('Could not request Instagram.', 404);
        }
        else {
            $this->returnError('You must specify a Instagram username (?u=...).', 400);
        }
        
        
        // "standard_resolution":{"url":"http:\/\/distilleryimage6.s3.amazonaws.com\/5ff1829036bc11e3b6c622000a1f92d1_7.jpg","width":612,"height":612}
        
        if (preg_match_all('/"standard_resolution":\{"url":"(http:[^"]+)","width":(\d+),"height":(\d+)\}/', $text, $matches))
        {
        	foreach($matches[0] as $key => $dummy)
        	{
        		$imageurl = stripslashes($matches[1][$key]);
        		$width = (int) $matches[2][$key];
        		$height = (int) $matches[3][$key];
        		
        		
        		$item = new \Item();
        		$item->uri = $imageurl;
        		$item->content = '<img src="' . htmlentities($imageurl) . '" width="'.$width.'" height="'.$height.'" />';
        		$item->title = basename($imageurl);
        		$this->items[] = $item;
        	}
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Instagram Bridge';
    }

    public function getURI(){
        return 'http://instagram.com/';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
