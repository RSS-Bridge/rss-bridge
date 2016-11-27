<?php
class ViadeoCompanyBridge extends BridgeAbstract{

	const MAINTAINER = "regisenguehard";
	const NAME = "Viadeo Company";
	const URI = "https://www.viadeo.com/";
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = "Returns most recent actus from Company on Viadeo. (http://www.viadeo.com/fr/company/<strong style=\"font-weight:bold;\">apple</strong>)";

    const PARAMETERS = array( array(
        'c'=>array(
            'name'=>'Company name',
            'required'=>true
        )
    ));

    public function collectData(){
        $html = '';
        $link = self::URI.'fr/company/'.$this->getInput('c');

        $html = getSimpleHTMLDOM($link)
          or returnServerError('Could not request Viadeo.');

        foreach($html->find('//*[@id="company-newsfeed"]/ul/li') as $element) {
            $title = $element->find('p', 0)->innertext;
            if ($title) {
                $item = array();
                $item['uri'] = $link;
                $item['title'] = mb_substr($element->find('p', 0)->innertext, 0 ,100);
                $item['content'] = $element->find('p', 0)->innertext;;
                $this->items[] = $item;
                $i++;
            }
        }
    }
}
