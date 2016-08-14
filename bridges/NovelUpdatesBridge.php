<?php
class NovelUpdatesBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "albirew";
		$this->name = "Novel Updates";
		$this->uri = "http://www.novelupdates.com/";
		$this->description = "Returns releases from Novel Updates";
		$this->update = "2016-08-09";
		$this->parameters[] =
		'[
			{
				"name" : "Novel URL",
				"identifier" : "n"
			}
		]';
	}

    public function collectData(array $param){
        if (!isset($param['n']))
            $this->returnError('You must specify the novel URL (/series/...)', 400);
        $thread = parse_url($param['n']) or $this->returnError('This URL seems malformed, please check it.', 400);
        if($thread['host'] !== 'www.novelupdates.com')
            $this->returnError('NovelUpdates URL only.', 400);
      	if(strpos($thread['path'], 'series/') === FALSE)
            $this->returnError('You must specify the novel URL.', 400);
        $url = 'http://www.novelupdates.com'.$thread['path'].'';
        $fullhtml = $this->file_get_html($url) or $this->returnError("Could not request NovelUpdates, novel not found", 404);
        $this->request = $fullhtml->find('h4.seriestitle', 0)->plaintext;
        // dirty fix for nasty simpledom bug: https://github.com/sebsauvage/rss-bridge/issues/259
        // forcefully removes tbody
        $html = $fullhtml->find('table#myTable', 0)->innertext;
        $html = stristr($html, '<tbody>'); //strip thead
        $html = stristr($html, '<tr>'); //remove tbody
        $html = str_get_html(stristr($html, '</tbody>', true)); //remove last tbody and get back as an array
        foreach($html->find('tr') as $element){
            $item = new \Item();
            $item->uri = $element->find('td', 2)->find('a', 0)->href;
            $item->title = $element->find('td', 2)->find('a', 0)->plaintext;
            $item->team = $element->find('td', 1)->innertext;
            $item->timestamp = strtotime($element->find('td', 0)->plaintext);
            $item->content = '<a href="'.$item->uri.'">'.$this->request.' - '.$item->title.'</a> by '.$item->team.'<br><a href="'.$item->uri.'">'.$fullhtml->find('div.seriesimg', 0)->innertext.'</a>';
            $this->items[] = $item;
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request.' - ' : '') .'Novel Updates';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
