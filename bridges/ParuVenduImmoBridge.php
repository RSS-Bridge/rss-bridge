<?php

class ParuVenduImmoBridge extends BridgeAbstract
{
    private $request = '';

	public function loadMetadatas() {

		$this->maintainer = "polo2ro";
		$this->name = "Paru Vendu Immobilier";
		$this->uri = "http://www.paruvendu.fr";
		$this->description = "Returns the ads from the first page of search result.";


        $this->parameters[] = array(
          'minarea'=>array(
            'name'=>'Minimal surface m²',
            'type'=>'number'
          ),
          'maxprice'=>array(
            'name'=>'Max price',
            'type'=>'number'
          ),
          'pa'=>array(
            'name'=>'Country code',
            'exampleValue'=>'FR'
          ),
          'lo'=>array('name'=>'department numbers or postal codes, comma-separated')
        );
	}

    public function collectData()
    {
        $html = '';
        $num = 20;
        $appartment = '&tbApp=1&tbDup=1&tbChb=1&tbLof=1&tbAtl=1&tbPla=1';
        $maison = '&tbMai=1&tbVil=1&tbCha=1&tbPro=1&tbHot=1&tbMou=1&tbFer=1';
        $link = $this->uri.'/immobilier/annonceimmofo/liste/listeAnnonces?tt=1'.$appartment.$maison;

        if (isset($param['minarea']['value'])) {
            $this->request .= ' '.$param['minarea']['value'].' m2';
            $link .= '&sur0='.urlencode($param['minarea']['value']);
        }

        if (isset($param['maxprice']['value'])) {
            $link .= '&px1='.urlencode($param['maxprice']['value']);
        }

        if (isset($param['pa']['value'])) {
            $link .= '&pa='.urlencode($param['pa']['value']);
        }

        if (isset($param['lo']['value'])) {
            $this->request .= ' In: '.$param['lo']['value'];
            $link .= '&lo='.urlencode($param['lo']['value']);
        }

        $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request paruvendu.');


        foreach($html->find('div.annonce a') as $element) {

            if (!$element->title) {
                continue;
            }

            $img ='';
            foreach($element->find('span.img img') as $img) {
                if ($img->original) {
                    $img = '<img src="'.$img->original.'" />';
                }
            }

            $desc = $element->find('span.desc')[0]->innertext;
            $desc = str_replace("voir l'annonce", '', $desc);

            $price = $element->find('span.price')[0]->innertext;

            list($href) = explode('#', $element->href);

            $item = array();
            $item['uri'] = $this->uri.$href;
            $item['title'] = $element->title;
            $item['content'] = $img.$desc.$price;
            $this->items[] = $item;

        }
    }

    public function getName(){
        return 'Paru Vendu Immobilier'.$this->request;
    }

    public function getCacheDuration(){
        return 10800; // 3 hours
    }
}
