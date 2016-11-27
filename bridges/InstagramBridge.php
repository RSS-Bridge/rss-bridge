<?php
class InstagramBridge extends BridgeAbstract{

	const MAINTAINER = "pauder";
	const NAME = "Instagram Bridge";
	const URI = "http://instagram.com/";
	const DESCRIPTION = "Returns the newest images";

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'username',
            'required'=>true
        )
    ));

    public function collectData(){
        $html = getSimpleHTMLDOM($this->getURI())
            or returnServerError('Could not request Instagram.');

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
        	$item['uri'] = self::URI.'p/'.$media->code.'/';
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
        return self::URI.urlencode($this->getInput('u'));
    }
}

