<?php
class ViadeoCompany extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "regisenguehard";
		$this->name = "Viadeo Company";
		$this->uri = "https://www.viadeo.com/";
		$this->description = "Returns most recent actus from Company on Viadeo. (http://www.viadeo.com/fr/company/<strong style=\"font-weight:bold;\">apple</strong>)";
		$this->update = "2015-12-22";

		$this->parameters[] =
		'[
			{
				"name" : "Company name",
				"identifier" : "c"
			}
		]';
	}

    public function collectData(array $param){
        $html = '';
        $link = 'http://www.viadeo.com/fr/company/'.$param[c];

        $html = file_get_html($link) or $this->returnError('Could not request Viadeo.', 404);

        foreach($html->find('//*[@id="company-newsfeed"]/ul/li') as $element) {
            $title = $element->find('p', 0)->innertext;
            if ($title) {
                $item = new \Item();
                $item->uri = $link;
                $item->title = mb_substr($element->find('p', 0)->innertext, 0 ,100);
                $item->content = $element->find('p', 0)->innertext;
                $item->thumbnailUri = str_replace('//', 'http://', $element->find('img.usage-article__image_only', 0)->src);
                $this->items[] = $item;
                $i++;
            }
        }
    }

    public function getName(){
        return 'Viadeo';
    }

    public function getURI(){
        return 'https://www.viadeo.com';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
