<?php

class ParuVenduImmoBridge extends BridgeAbstract
{
    private $request = '';

	public function loadMetadatas() {

		$this->maintainer = "polo2ro";
		$this->name = "Paru Vendu Immobilier";
		$this->uri = "http://www.paruvendu.fr";
		$this->description = "Returns the ads from the first page of search result.";
		$this->update = "2016-08-09";


		$this->parameters[] =
		'[
			{
				"name": "Minimal surface m²",
				"type" : "number",
				"identifier" : "minarea"
			},
			{
				"name" : "Max price",
				"type" : "number",
				"identifier" : "maxprice"
			},
			{
				"name" : "Country code",
				"type" : "text",
				"identifier" : "pa",
				"exampleValue" : "FR"
			},
			{
				"name" : "department numbers or postal codes, comma-separated",
				"type" : "text",
				"identifier" : "lo"
			}

		]';
	}

    public function collectData(array $param)
    {
        $html = '';
        $num = 20;
        $appartment = '&tbApp=1&tbDup=1&tbChb=1&tbLof=1&tbAtl=1&tbPla=1';
        $maison = '&tbMai=1&tbVil=1&tbCha=1&tbPro=1&tbHot=1&tbMou=1&tbFer=1';
        $link = $this->uri.'/immobilier/annonceimmofo/liste/listeAnnonces?tt=1'.$appartment.$maison;
        
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

        $html = $this->file_get_html($link) or $this->returnError('Could not request paruvendu.', 404);


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
            
            $item = new \Item();
            $item->uri = $this->uri.$href;
            $item->title = $element->title;
            $item->content = $img.$desc.$price;
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
