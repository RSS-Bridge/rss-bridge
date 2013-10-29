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
        


        $image = '"(\w+)":\{"url":"(http:[^"]+)","width":(\d+),"height":(\d+)\}';
        
        if (preg_match_all('/"created_time":"(\d+)"\s*,\s*"images":\{'.$image.','.$image.','.$image.'\}/', $text, $matches))
        {
        	foreach($matches[0] as $key => $dummy)
        	{
        		$timestamp = (int) $matches[1][$key];
        		$images = array();
        		
        		$pos = 2;
        		for($i = 0; $i < 3; $i++)
        		{
        			$imagetype = $matches[$pos++][$key];
	        		
	        		$images[$imagetype] = array(
	        			'url' => stripslashes($matches[$pos++][$key]),
	        			'width' => (int) $matches[$pos++][$key],
        				'height' => (int) $matches[$pos++][$key]	
	        		);
        		
        		}
        		
        		
        		$item = new \Item();
        		$item->uri = $images['standard_resolution']['url'];
        		$item->content = '<img src="' . htmlentities($images['standard_resolution']['url']) . '" width="'.$images['standard_resolution']['width'].'" height="'.$images['standard_resolution']['height'].'" />';
        		$item->title = basename($images['standard_resolution']['url']);
        		$item->timestamp = $timestamp;
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
