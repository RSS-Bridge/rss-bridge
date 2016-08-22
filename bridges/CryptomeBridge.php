<?php
class CryptomeBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "BoboTiG";
		$this->name = "Cryptome";
		$this->uri = "http://cryptome.org/";
		$this->description = "Returns the N most recent documents.";

        $this->parameters[] = array(
          'n'=>array(
            'name'=>'number of elements',
            'type'=>'number',
            'exampleValue'=>10
          )
        );
	}


    public function collectData(array $param){
        $html = '';
        $num = 20;
        $link = 'http://cryptome.org/';
        // If you want HTTPS access instead, uncomment the following line:
        //$link = 'https://secure.netsolhost.com/cryptome.org/';

        $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request Cryptome.');
        if (!empty($param['n'])) {   /* number of documents */
            $num = min(max(1, $param['n']+0), $num);
        }


        foreach($html->find('pre') as $element) {
            for ( $i = 0; $i < $num; ++$i ) {
                $item = array();
                $item['uri'] = $link.substr($element->find('a', $i)->href, 20);
                $item['title'] = substr($element->find('b', $i)->plaintext, 22);
                $item['content'] = preg_replace('#http://cryptome.org/#', $link, $element->find('b', $i)->innertext);
                $this->items[] = $item;
            }
            break;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
