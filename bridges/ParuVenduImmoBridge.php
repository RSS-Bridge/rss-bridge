<?php
/**
* RssBridge Paru Vendu Immo
* Retrieve lastest documents from http://www.paruvendu.fr/immobilier/.
* Returns the N most recent documents, sorting by date (most recent first).
* 2014-05-25
*
* @name Paru Vendu Immobilier
* @homepage http://www.paruvendu.fr/immobilier/
* @description Returns the N most recent documents.
* @maintainer polo2ro
* @use1(minarea="Min area",maxprice="Max price",pa="Country code",lo="department number")
*/
class ParuVenduImmoBridge extends BridgeAbstract
{
    private $request = '';

    public function collectData(array $param)
    {
        $html = '';
        $num = 20;
        $link = $this->getURI().'/immobilier/annonceimmofo/liste/listeAnnonces?tt=1&tbMai=1&tbVil=1&tbCha=1&tbPro=1&tbHot=1&tbMou=1&tbFer=1';
        
        if (isset($param['minarea'])) {
            $this->request .= ' '.$param['minarea'].' m2';
            $link .= '&sur0='.urlencode($param['minarea']);
        }

        if (isset($param['maxprice'])) {
            $link .= '&px1='.urlencode($param['maxprice']);
        }
        
        if (isset($param['pa'])) {
            $link .= '&pa='.urlencode($param['pa']);
        }
        
        if (isset($param['lo'])) {
            $this->request .= ' In: '.$param['lo'];
            $link .= '&lo='.urlencode($param['lo']);
        }

        $html = file_get_html($link) or $this->returnError('Could not request paruvendu.', 404);


        foreach($html->find('div.annonce a') as $element) {
            
            $img ='';
            foreach($element->find('span.img img') as $img) {
                if ($img->original) {
                    $img = '<img src="'.$img->original.'" />';
                }
            }
            
            $desc = $element->find('span.desc')[0]->innertext;
            $desc = str_replace("voir l'annonce", '', $desc);
            
            $price = $element->find('span.price')[0]->innertext;

            $item = new \Item();
            $item->uri = $this->getURI().$element->href;
            $item->title = $element->title;
            $item->content = $img.$desc.$price;
            $this->items[] = $item;

        }
    }

    public function getName(){
        return 'Paru Vendu Immobilier'.$this->request;
    }

    public function getURI(){
        return 'http://www.paruvendu.fr';
    }

    public function getCacheDuration(){
        return 10800; // 3 hours
    }
}
