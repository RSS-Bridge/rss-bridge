<?php
/**
* RssBridgeCryptome
* Retrieve lastest documents from Cryptome.
* Returns the N most recent documents, sorting by date (most recent first).
*
* @name Cryptome
* @description Returns the N most recent documents.
* @use1(n="number")
*/
class CryptomeBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
        $num = 20;
        $link = 'http://cryptome.org/';
        // If you want HTTPS access instead, uncomment the following line:
        //$link = 'https://secure.netsolhost.com/cryptome.org/';

        $html = file_get_html($link) or $this->returnError('Could not request Cryptome.', 404);
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

    public function getName(){
        return 'Cryptome';
    }

    public function getURI(){
        return 'https://secure.netsolhost.com/cryptome.org/';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
