<?php
class SexactuBridge extends BridgeAbstract{

	const MAINTAINER = "Riduidel";
	const NAME = "Sexactu";
	const URI = "https://www.gqmagazine.fr";
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = "Sexactu via rss-bridge";

    public function collectData(){
$find = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'novembre', 'décembre');
$replace = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

    $html = getSimpleHTMLDOM($this->getURI()) or returnServerError('Could not request '.$this->getURI());

        foreach($html->find('.content-holder') as $contentHolder) {
            // only use first list as second one only contains pages numbers
            $articles = $contentHolder->find('ul', 0);
            foreach($articles->find('li') as $element) {
                // if you ask about that method_exists, there seems to be a bug in simple html dom
                // see stackoverflow for more details : http://stackoverflow.com/a/10828479/15619
                if(is_object($element)) {
                    $item = array();
                    // various metadata
                    $titleBlock = $element->find('.title-holder', 0);
                    if(is_object($titleBlock)) {
                        $titleDetails = $titleBlock->find('.article-title',0);
                        $titleData = $titleDetails->find('h2', 0)->find('a',0);
                        $titleTimestamp =$titleDetails->find('h4',0);
                        $item['title'] = $this->correctCase(trim($titleData->innertext));
                        $item['uri'] = self::URI.$titleData->href;

                        // Fugly date parsing due to the fact my DNS-323 doesn't support php intl extension
                        $dateText = $titleTimestamp->innertext;
                        $dateText = substr($dateText, strpos($dateText,',')+1);
                        $dateText = str_replace($find, $replace, strtolower($dateText));
                        $date = strtotime($dateText);
                        $item['timestamp'] = $date;

                        $item['author'] = "Maïa Mazaurette";
                        $elementText = $element->find('.text-container', 0);
                        // don't forget to replace images server url with gq one
                        foreach($elementText->find('img') as $image) {
                            $image->src = self::URI.$image->src;
                        }
                        $item['content'] = $elementText->innertext;
                        $this->items[] = $item;
                    }

                }

            }
        }
    }

    public function getURI(){
        return self::URI.'/sexactu';
    }

    private function correctCase($str) {
        $sentences=explode('.', mb_strtolower($str, "UTF-8"));
        $str="";
        $sep="";
        foreach ($sentences as $sentence)
        {
           //upper case first char
           $sentence=ucfirst(trim($sentence));

           //append sentence to output
           $str=$str.$sep.$sentence;
           $sep=". ";
        }
        return $str;
    }
}
