<?php
class InstagramBridge extends BridgeAbstract{

    private $request;

    public function loadMetadatas() {

		$this->maintainer = "pauder";
		$this->name = "Instagram Bridge";
		$this->uri = "http://instagram.com/";
		$this->description = "Returns the newest images";

        $this->parameters[] = array(
            'u'=>array(
                'name'=>'username',
                'required'=>true
            )
        );

	}

    public function collectData(){
        $param=$this->parameters[$this->queriedContext];
        $html = '';
        if (isset($param['u']['value'])) {   /* user timeline mode */
            $this->request = $param['u']['value'];
            $html = $this->getSimpleHTMLDOM('http://instagram.com/'.urlencode($this->request)) or $this->returnServerError('Could not request Instagram.');
        }
        else {
            $this->returnClientError('You must specify a Instagram username (?u=...).');
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

        	$item = array();
        	$item['uri'] = "https://instagram.com/p/".$media->code."/";
        	$item['content'] = '<img src="' . htmlentities($media->display_src) . '" />';
        	if (isset($media->caption))
        	{
        		$item['title'] = $media->caption;
        	} else {
        		$item['title'] = basename($media->display_src);
        	}
        	$item['timestamp'] = $media->date;
        	$this->items[] = $item;

        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Instagram Bridge';
    }
}
