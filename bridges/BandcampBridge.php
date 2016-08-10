<?php
class BandcampBridge extends BridgeAbstract{

    private $request;

	public function loadMetadatas() {

		$this->maintainer = "sebsauvage";
		$this->name = "Bandcamp Tag";
		$this->uri = "http://bandcamp.com/";
		$this->description = "New bandcamp release by tag";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "tag",
				"type" : "text",
				"identifier" : "tag"

			}
		]';
	}

    public function collectData(array $param){
        $html = '';
        if (isset($param['tag'])) {
            $this->request = $param['tag'];
            $html = $this->file_get_html('http://bandcamp.com/tag/'.urlencode($this->request).'?sort_field=date') or $this->returnError('No results for this query.', 404);
        }
        else {
            $this->returnError('You must specify tag (/tag/...)', 400);
        }

        foreach($html->find('li.item') as $release) {
            $script = $release->find('div.art', 0)->getAttribute('onclick');
            $uri = ltrim($script, "return 'url(");
            $uri = rtrim($uri, "')");

            $item = new \Item();
            $item->author = $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item->title = $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item->content = '<img src="' . $uri . '"/><br/>' . $release->find('div.itemsubtext',0)->plaintext . ' - ' . $release->find('div.itemtext',0)->plaintext;
            $item->id = $release->find('a',0)->getAttribute('href');
            $item->uri = $release->find('a',0)->getAttribute('href');
            $this->items[] = $item;
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Bandcamp Tag';
    }

    public function getCacheDuration(){
        return 600; // 10 minutes
    }
}
