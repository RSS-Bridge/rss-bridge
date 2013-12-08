<?php
/**
* RssBridgeYoutube 
* Returns the newest videos
*
* @name Youtube Bridge
* @description Returns the newest videos
* @use1(u="username")
*/
class YoutubeBridge extends BridgeAbstract{
    
    private $request;
    
    public function collectData(array $param){
        $html = '';
        if (isset($param['u'])) {   /* user timeline mode */
            $this->request = $param['u'];
            $html = file_get_html('https://www.youtube.com/user/'.urlencode($this->request).'/videos') or $this->returnError('Could not request Youtube.', 404);
        }
        else {
            $this->returnError('You must specify a Youtbe username (?u=...).', 400);
        }
        
    
        foreach($html->find('li.channels-content-item') as $element) {
            
            
            $opts = array('http' =>
              array(
                "method" => "GET",
                'header'  => "User-Agent: Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)\r\n"
                )
            );
            $context  = stream_context_create($opts);
            
            $html_video = file_get_contents('http://www.youtube.com'.$element->find('a',0)->href, false, $context);
            
            if(!preg_match('/stream_map=(.[^&]*?)&/i',$html_video,$match))
            {
                //$this->returnError ("Error Locating Downlod URL's", 400);
            }
    
            if(!preg_match('/stream_map=(.[^&]*?)(?:\\\\|&)/i',$html_video,$match))
            {
                //$this->returnError ("Problem", 400);
            }
    
            $fmt_url =  urldecode($match[1]);
       
            $urls = explode(',',$fmt_url);
                    
            $videos = array();
    
            foreach($urls as $url)
            {            
                if(preg_match('/itag=([0-9]+)/',$url,$tm) && preg_match('/sig=(.*?)&/', $url , $si) && preg_match('/url=(.*?)&/', $url , $um))
                {
                    $u = urldecode($um[1]);
                    $videos[$tm[1]] = $u.'&signature='.$si[1];
                }
            }
            
            $codecs = array();
            $codecs[13] = "video/3gpp";
            $codecs[17] =  "video/3gpp";
            $codecs[36] =  "video/3gpp";
            $codecs[5]  =  "video/x-flv";
            $codecs[6]  =  "video/x-flv";
            $codecs[34] =  "video/x-flv";
            $codecs[35] =  "video/x-flv";
            $codecs[43] =  "video/webm";
            $codecs[44] =  "video/webm";
            $codecs[45] =  "video/webm";
            $codecs[18] =  "video/mp4";
            $codecs[22] =  "video/mp4";
            $codecs[37] =  "video/mp4";
            $codecs[33] =  "video/mp4";

            $item = new \Item();
            $item->uri = 'https://www.youtube.com'.$element->find('a',0)->href;
            $item->thumbnailUri = 'https:'.$element->find('img',0)->src;
            $item->attachments = array();
            foreach ($videos as $key => $value){
                $item->attachments[] = array("URL" => htmlspecialchars($value), "codec" => $codecs[$key]);
            }
            $item->title = trim($element->find('h3',0)->plaintext);
            $item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
            $this->items[] = $item;
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Youtube Bridge';
    }

    public function getURI(){
        return 'https://www.youtube.com/';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
