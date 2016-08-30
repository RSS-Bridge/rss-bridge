<?php
class InstagramBridge extends BridgeAbstract{

	public $maintainer = "pauder";
	public $name = "Instagram Bridge";
	public $uri = "http://instagram.com/";
	public $description = "Returns the newest images";

    public $parameters = array( array(
        'u'=>array(
            'name'=>'username',
            'required'=>true
        )
    ));

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->getURI())
            or $this->returnServerError('Could not request Instagram.');

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
        	$item['uri'] = $this->uri.'/p/'.$media->code.'/';
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
        return $this->getInput('u') .' - Instagram Bridge';
    }

    public function getURI(){
        return $this->uri.urlencode($this->getInput('u'));
    }
}

