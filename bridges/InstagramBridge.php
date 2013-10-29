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
            $html = file_get_html('http://instagram.com/'.urlencode($this->request)) or $this->returnError('Could not request Instagram.', 404);
        }
        else {
            $this->returnError('You must specify a Instagram username (?u=...).', 400);
        }
        
        $innertext = null;
        
        foreach($html->find('script') as $script)
        {
        	if ('' === $script->innertext) {
        		continue;
        	}
        	
        	$pos = strpos($script->innertext, 'window._jscalls');
        	if (false === $pos)
        	{
        		continue;
        	}
        	
        	$innertext = $script->innertext;
        	
        	break;
        }
        
        
        $json = trim(substr($innertext, $pos+15), ' =;');
        $pos = strpos($json, '}]],');
        $json = substr($json, $pos+4, -4);
        $data = json_decode($json);

        $userMedia = $data[2][0]->props->userMedia;


        foreach($userMedia as $media)
        {
        	$image = $media->images->standard_resolution;
        
        
        	$item = new \Item();
        	$item->uri = $media->link;
        	$item->content = '<img src="' . htmlentities($image->url) . '" width="'.htmlentities($image->width).'" height="'.htmlentities($image->height).'" />';
        	if (isset($media->caption))
        	{
        		$item->title = $media->caption->text;
        	} else {
        		$item->title = basename($image->url);
        	}
        	$item->timestamp = $media->created_time;
        	$this->items[] = $item;
        	
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Instagram Bridge';
    }

    public function getURI(){
        return 'http://instagram.com/';
    }

    public function getCacheDuration(){
    	return 0;
        return 21600; // 6 hours
    }
}
