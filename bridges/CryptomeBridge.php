<?php
class CryptomeBridge extends BridgeAbstract{

    const MAINTAINER = "BoboTiG";
    const NAME = "Cryptome";
    const URI = "https://cryptome.org/";
    const CACHE_TIMEOUT = 21600; //6h
    const DESCRIPTION = "Returns the N most recent documents.";

    const PARAMETERS = array( array(
        'n'=>array(
            'name'=>'number of elements',
            'type'=>'number',
            'defaultValue'=>20,
            'exampleValue'=>10
        )
    ));

    public function collectData(){
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request Cryptome.');
        $number=$this->getInput('n');
        if (!empty($number)) {   /* number of documents */
            $num = min($number, 20);
        }


        foreach($html->find('pre') as $element) {
            for ( $i = 0; $i < $num; ++$i ) {
                $item = array();
                $item['uri'] = self::URI.substr($element->find('a', $i)->href, 20);
                $item['title'] = substr($element->find('b', $i)->plaintext, 22);
                $item['content'] = preg_replace('#http://cryptome.org/#', self::URI, $element->find('b', $i)->innertext);
                $this->items[] = $item;
            }
            break;
        }
    }
}
