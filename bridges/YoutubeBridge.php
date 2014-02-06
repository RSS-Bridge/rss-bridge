<?php
/**
* RssBridgeYoutube
* Returns the newest videos
*
* @name Youtube Bridge
* @description Returns the newest videos
* @use1(u="username")
*/
class YoutubeBridge extends BridgeAbstract
{
    private $request;

    public function collectData(array $param)
    {
        $html = '';
        if (isset($param['u'])) {   /* user timeline mode */
            $this->request = $param['u'];
            $html = file_get_html('https://www.youtube.com/user/'.urlencode($this->request).'/videos') or $this->returnError('Could not request Youtube.', 404);
        } else {
            $this->returnError('You must specify a Youtbe username (?u=...).', 400);
        }

        foreach ($html->find('li.channels-content-item') as $element) {
            $item = new \Item();
            $item->uri = 'https://www.youtube.com'.$element->find('a',0)->href;
            $item->thumbnailUri = 'https:'.$element->find('img',0)->src;
            $item->title = trim($element->find('h3',0)->plaintext);
            $item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        return (!empty($this->request) ? $this->request .' - ' : '') .'Youtube Bridge';
    }

    public function getURI()
    {
        return 'https://www.youtube.com/';
    }

    public function getCacheDuration()
    {
        return 21600; // 6 hours
    }
}
