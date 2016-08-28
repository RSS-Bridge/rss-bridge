<?php
class CryptomeBridge extends BridgeAbstract{

    public $maintainer = "BoboTiG";
    public $name = "Cryptome";
    public $uri = "https://cryptome.org/";
    public $description = "Returns the N most recent documents.";

    public $parameters = array( array(
        'n'=>array(
            'name'=>'number of elements',
            'type'=>'number',
            'defaultValue'=>20,
            'exampleValue'=>10
        )
    ));

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->uri)
            or $this->returnServerError('Could not request Cryptome.');
        if (!empty($this->getInput('n'))) {   /* number of documents */
            $num = min($this->getInput('n'), 20);
        }


        foreach($html->find('pre') as $element) {
            for ( $i = 0; $i < $num; ++$i ) {
                $item = array();
                $item['uri'] = $this->uri.substr($element->find('a', $i)->href, 20);
                $item['title'] = substr($element->find('b', $i)->plaintext, 22);
                $item['content'] = preg_replace('#http://cryptome.org/#', $this->uri, $element->find('b', $i)->innertext);
                $this->items[] = $item;
            }
            break;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
