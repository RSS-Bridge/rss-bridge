<?php
class CryptomeBridge extends BridgeAbstract{

    public $maintainer = "BoboTiG";
    public $name = "Cryptome";
    public $uri = "http://cryptome.org/";
    public $description = "Returns the N most recent documents.";

    public $parameters = array( array(
        'n'=>array(
            'name'=>'number of elements',
            'type'=>'number',
            'exampleValue'=>10
        )
    ));

    public function collectData(){
        $param=$this->parameters[$this->queriedContext];
        $html = '';
        $num = 20;
        $link = 'http://cryptome.org/';
        // If you want HTTPS access instead, uncomment the following line:
        //$link = 'https://secure.netsolhost.com/cryptome.org/';

        $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request Cryptome.');
        if (!empty($param['n']['value'])) {   /* number of documents */
            $num = min(max(1, $param['n']['value']+0), $num);
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
