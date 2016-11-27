<?php
class ParuVenduImmoBridge extends BridgeAbstract
{
	const MAINTAINER = "polo2ro";
	const NAME = "Paru Vendu Immobilier";
	const URI = "http://www.paruvendu.fr";
	const CACHE_TIMEOUT = 10800; // 3h
	const DESCRIPTION = "Returns the ads from the first page of search result.";


    const PARAMETERS = array( array(
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
    ));

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI())
          or returnServerError('Could not request paruvendu.');

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
            $item['uri'] = self::URI.$href;
            $item['title'] = $element->title;
            $item['content'] = $img.$desc.$price;
            $this->items[] = $item;

        }
    }

    public function getURI(){
        $appartment = '&tbApp=1&tbDup=1&tbChb=1&tbLof=1&tbAtl=1&tbPla=1';
        $maison = '&tbMai=1&tbVil=1&tbCha=1&tbPro=1&tbHot=1&tbMou=1&tbFer=1';
        $link = self::URI.'/immobilier/annonceimmofo/liste/listeAnnonces?tt=1'.$appartment.$maison;

        if ($this->getInput('minarea')) {
            $link .= '&sur0='.urlencode($this->getInput('minarea'));
        }

        if ($this->getInput('maxprice')) {
            $link .= '&px1='.urlencode($this->getInput('maxprice'));
        }

        if ($this->getInput('pa')) {
            $link .= '&pa='.urlencode($this->getInput('pa'));
        }

        if ($this->getInput('lo')) {
            $link .= '&lo='.urlencode($this->getInput('lo'));
        }
        return $link;
    }

    public function getName(){
        $request='';
        $minarea=$this->getInput('minarea');
        if(!empty($minarea)){
            $request .= ' '.$minarea.' m2';
        }
        $location=$this->getInput('lo');
        if(!empty($location)){
            $request .= ' In: '.$location;
        }
        return 'Paru Vendu Immobilier'.$request;
    }
}
