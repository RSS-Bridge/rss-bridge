<?php
class BandcampBridge extends BridgeAbstract{

    const MAINTAINER = "sebsauvage";
    const NAME = "Bandcamp Tag";
    const URI = "http://bandcamp.com/";
    const CACHE_TIMEOUT = 600; // 10min
    const DESCRIPTION = "New bandcamp release by tag";
    const PARAMETERS = array( array(
        'tag'=>array(
            'name'=>'tag',
            'type'=>'text',
            'required'=>true
        )
    ));

    public function collectData(){
        $html = getSimpleHTMLDOM($this->getURI())
            or returnServerError('No results for this query.');

        foreach($html->find('li.item') as $release) {
            $script = $release->find('div.art', 0)->getAttribute('onclick');
            $uri = ltrim($script, "return 'url(");
            $uri = rtrim($uri, "')");

            $item = array();
            $item['author'] = $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item['title'] = $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item['content'] = '<img src="' . $uri . '"/><br/>' . $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item['id'] = $release->find('a',0)->getAttribute('href');
            $item['uri'] = $release->find('a',0)->getAttribute('href');
            $this->items[] = $item;
        }
    }

    public function getURI(){
        return self::URI.'tag/'.urlencode($this->getInput('tag')).'?sort_field=date';
    }

    public function getName(){
        return $this->getInput('tag') .' - '.'Bandcamp Tag';
    }
}
