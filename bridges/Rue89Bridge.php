<?php
class Rue89Bridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "pit-fgfjiudghdf";
		$this->name = "Rue89";
		$this->uri = "http://rue89.nouvelobs.com/";
		$this->description = "Returns the 5 newest posts from Rue89 (full text)";
		$this->update = "2016-08-09";

	}

	private function rue89getDatas($url){

		$url = "http://api.rue89.nouvelobs.com/export/mobile2/node/" . str_replace(" ", "", substr($url, -8)) . "/full";
		$datas = json_decode(file_get_contents($url), true);

		return $datas["node"];

	}

    public function collectData(array $param){

        $html = $this->file_get_html('http://api.rue89.nouvelobs.com/feed') or $this->returnError('Could not request Rue89.', 404);

        $limit = 0;
        foreach($html->find('item') as $element) {

        	if($limit < 5) {

				$datas = $this->rue89getDatas(str_replace('#commentaires', '', ($element->find('comments', 0)->plaintext)));

				$item = new \Item();
				$item->title = $datas["title"];
				$item->author = $datas["author"][0]["name"];
				$item->timestamp = $datas["updated"];
				$item->content = $datas["body"];
				$item->uri = $datas["url"];

				$this->items[] = $item;

			}
        }

    }
}
