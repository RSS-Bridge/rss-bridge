<?php
/**
* BandcampTagRSS
*
* @name Bandcamp Tag
* @description New bandcamp release by tag
* @use1(tag="tag")
*/
class BandcampBridge extends BridgeAbstract{
    
    private $request;

    public function collectData(array $param){
        $html = '';
        if (isset($param['tag'])) {
            $this->request = $param['tag'];
            $html = file_get_html('http://bandcamp.com/tag/'.urlencode($this->request).'?sort_field=date') or $this->returnError('No results for this query.', 404);
        }
        else {
            $this->returnError('You must specify tag (/tag/...)', 400);
        }

        foreach($html->find('li.item') as $release) {
            $item = new \Item();
            $item->name = $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item->title = $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item->content = '<img src="' . $release->find('img.art',0)->src . '"/><br/>' . $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item->id = $release->find('a',0)->getAttribute('href');
            $item->uri = $release->find('a',0)->getAttribute('href');
            $this->items[] = $item;
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Bandcamp Tag';
    }

    public function getURI(){
        return 'http://bandcamp.com';
    }

    public function getCacheDuration(){
        return 600; // 10 minutes
    }
}
