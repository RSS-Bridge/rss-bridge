<?php
class ViadeoCompany extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "regisenguehard";
		$this->name = "Viadeo Company";
		$this->uri = "https://www.viadeo.com/";
		$this->description = "Returns most recent actus from Company on Viadeo. (http://www.viadeo.com/fr/company/<strong style=\"font-weight:bold;\">apple</strong>)";

        $this->parameters[] = array(
          'c'=>array(
            'name'=>'Company name',
            'required'=>true
          )
        );
	}

    public function collectData(array $param){
        $html = '';
        $link = 'http://www.viadeo.com/fr/company/'.$param['c'];

        $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request Viadeo.');

        foreach($html->find('//*[@id="company-newsfeed"]/ul/li') as $element) {
            $title = $element->find('p', 0)->innertext;
            if ($title) {
                $item = new \Item();
                $item->uri = $link;
                $item->title = mb_substr($element->find('p', 0)->innertext, 0 ,100);
                $item->content = $element->find('p', 0)->innertext;;
                $this->items[] = $item;
                $i++;
            }
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
