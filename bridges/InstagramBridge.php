<?php
class InstagramBridge extends BridgeAbstract{

    private $request;

    public function loadMetadatas() {

		$this->maintainer = "pauder";
		$this->name = "Instagram Bridge";
		$this->uri = "http://instagram.com/";
		$this->description = "Returns the newest images";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "username",
				"identifier" : "u"
			}
		]';

	}

    public function collectData(array $param){
        $html = '';
        if (isset($param['u'])) {   /* user timeline mode */
            $this->request = $param['u'];
            $html = $this->file_get_html('http://instagram.com/'.urlencode($this->request)) or $this->returnError('Could not request Instagram.', 404);
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
        	
        	$pos = strpos(trim($script->innertext), 'window._sharedData');
        	if (0 !== $pos)
        	{
        		continue;
        	}
        	
        	$innertext = $script->innertext;
        	break;
        }

        $json = trim(substr($innertext, $pos+18), ' =;');
        $data = json_decode($json);
        
        
        
        $userMedia = $data->entry_data->ProfilePage[0]->user->media->nodes;

        foreach($userMedia as $media)
        {

        	$item = new \Item();
        	$item->uri = "https://instagram.com/p/".$media->code."/";
        	$item->content = '<img src="' . htmlentities($media->display_src) . '" />';
        	if (isset($media->caption))
        	{
        		$item->title = $media->caption;
        	} else {
        		$item->title = basename($media->display_src);
        	}
        	$item->timestamp = $media->date;
        	$this->items[] = $item;
        	
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Instagram Bridge';
    }
}
