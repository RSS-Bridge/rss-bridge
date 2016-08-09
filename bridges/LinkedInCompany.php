<?php
class LinkedInCompany extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "regisenguehard";
		$this->name = "LinkedIn Company";
		$this->uri = "https://www.linkedin.com/";
		$this->description = "Returns most recent actus from Company on LinkedIn. (https://www.linkedin.com/company/<strong style=\"font-weight:bold;\">apple</strong>)";
		$this->update = "2016-08-09";

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
        $link = 'https://www.linkedin.com/company/'.$param[c];

        $html = $this->file_get_html($link) or $this->returnError('Could not request LinkedIn.', 404);

        foreach($html->find('//*[@id="my-feed-post"]/li') as $element) {
            $title = $element->find('span.share-body', 0)->innertext;
            if ($title) {
                $item = new \Item();
                $item->uri = $link;
                $item->title = mb_substr(strip_tags($element->find('span.share-body', 0)->innertext), 0 ,100);
                $item->content = strip_tags($element->find('span.share-body', 0)->innertext);
                $this->items[] = $item;
                $i++;
            }
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
