<?php
class NovelUpdatesBridge extends BridgeAbstract{

	public $maintainer = "albirew";
	public $name = "Novel Updates";
	public $uri = "http://www.novelupdates.com/";
	public $description = "Returns releases from Novel Updates";
	public $parameters = array( array(
        'n'=>array(
            'name'=>'Novel URL',
            'required'=>true
        )
    ));

    public function collectData(){
        if (!isset($this->getInput('n')))
            $this->returnClientError('You must specify the novel URL (/series/...)');
        $thread = parse_url($this->getInput('n')) or $this->returnClientError('This URL seems malformed, please check it.');
        if($thread['host'] !== 'www.novelupdates.com')
            $this->returnClientError('NovelUpdates URL only.');
      	if(strpos($thread['path'], 'series/') === FALSE)
            $this->returnClientError('You must specify the novel URL.');
        $url = 'http://www.novelupdates.com'.$thread['path'].'';
        $fullhtml = $this->getSimpleHTMLDOM($url) or $this->returnServerError("Could not request NovelUpdates, novel not found");
        $this->request = $fullhtml->find('h4.seriestitle', 0)->plaintext;
        // dirty fix for nasty simpledom bug: https://github.com/sebsauvage/rss-bridge/issues/259
        // forcefully removes tbody
        $html = $fullhtml->find('table#myTable', 0)->innertext;
        $html = stristr($html, '<tbody>'); //strip thead
        $html = stristr($html, '<tr>'); //remove tbody
        $html = str_get_html(stristr($html, '</tbody>', true)); //remove last tbody and get back as an array
        foreach($html->find('tr') as $element){
            $item = array();
            $item['uri'] = $element->find('td', 2)->find('a', 0)->href;
            $item['title'] = $element->find('td', 2)->find('a', 0)->plaintext;
            $item['team'] = $element->find('td', 1)->innertext;
            $item['timestamp'] = strtotime($element->find('td', 0)->plaintext);
            $item['content'] = '<a href="'.$item['uri'].'">'.$this->request.' - '.$item['title'].'</a> by '.$item['team'].'<br><a href="'.$item['uri'].'">'.$fullhtml->find('div.seriesimg', 0)->innertext.'</a>';
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
