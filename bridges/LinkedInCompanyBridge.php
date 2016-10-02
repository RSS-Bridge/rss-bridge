<?php
class LinkedInCompanyBridge extends BridgeAbstract{

	const MAINTAINER = "regisenguehard";
	const NAME = "LinkedIn Company";
	const URI = "https://www.linkedin.com/";
	const CACHE_TIMEOUT = 21600; //6
	const DESCRIPTION = "Returns most recent actus from Company on LinkedIn. (https://www.linkedin.com/company/<strong style=\"font-weight:bold;\">apple</strong>)";

    const PARAMETERS = array( array(
        'c'=>array(
            'name'=>'Company name',
            'required'=>true
        )
    ));

    public function collectData(){
        $html = '';
        $link = self::URI.'company/'.$this->getInput('c');

        $html = getSimpleHTMLDOM($link)
            or returnServerError('Could not request LinkedIn.');

        foreach($html->find('//*[@id="my-feed-post"]/li') as $element) {
            $title = $element->find('span.share-body', 0)->innertext;
            if ($title) {
                $item = array();
                $item['uri'] = $link;
                $item['title'] = mb_substr(strip_tags($element->find('span.share-body', 0)->innertext), 0 ,100);
                $item['content'] = strip_tags($element->find('span.share-body', 0)->innertext);
                $this->items[] = $item;
                $i++;
            }
        }
    }
}
