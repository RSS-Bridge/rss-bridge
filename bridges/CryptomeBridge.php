<?php
class CryptomeBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "BoboTiG";
		$this->name = "Cryptome";
		$this->uri = "http://cryptome.org/";
		$this->description = "Returns the N most recent documents.";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "number of elements",
				"identifier" : "n",
				"type" : "number",
				"exampleValue" : "10"
			}
		]';
	}


    public function collectData(array $param){
        $html = '';
        $num = 20;
        $link = 'http://cryptome.org/';
        // If you want HTTPS access instead, uncomment the following line:
        //$link = 'https://secure.netsolhost.com/cryptome.org/';

        $html = $this->file_get_html($link) or $this->returnError('Could not request Cryptome.', 404);
        if (!empty($param['n'])) {   /* number of documents */
            $num = min(max(1, $param['n']+0), $num);
        }


        foreach($html->find('pre') as $element) {
            for ( $i = 0; $i < $num; ++$i ) {
                $item = new \Item();
                $item->uri = $link.substr($element->find('a', $i)->href, 20);
                $item->title = substr($element->find('b', $i)->plaintext, 22);
                $item->content = preg_replace('#http://cryptome.org/#', $link, $element->find('b', $i)->innertext);
                $this->items[] = $item;
            }
            break;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
