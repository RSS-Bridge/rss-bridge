<?php
/**
* Unfortunatly, Sexactu do not provide a RSS stream, which prevents me from upgrading this to the mugnificent RssExpander framework
* @name Sexactu
* @description Sexactu via rss-bridge
* @update 04/02/2014
*/
define("GQ", "http://www.gqmagazine.fr");
class Sexactu extends BridgeAbstract{

    public function collectData(array $param){
$find = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'novembre', 'décembre');
$replace = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

    $html = file_get_html($this->getURI()) or $this->returnError('Could not request '.$this->getURI(), 404);

        foreach($html->find('.content-holder') as $contentHolder) {
            // only use first list as second one only contains pages numbers 
            $articles = $contentHolder->find('ul', 0);
            foreach($articles->find('li') as $element) {
                // if you ask about that method_exists, there seems to be a bug in simple html dom
                // see stackoverflow for more details : http://stackoverflow.com/a/10828479/15619
                if(is_object($element)) {
                    $item = new Item();
                    // various metadata
                    $titleBlock = $element->find('.title-holder', 0);
                    if(is_object($titleBlock)) {
                        $titleDetails = $titleBlock->find('.article-title',0);
                        $titleData = $titleDetails->find('h2', 0)->find('a',0);
                        $titleTimestamp =$titleDetails->find('h4',0);
                        $item->title = $this->correctCase(trim($titleData->innertext));
                        $item->uri = GQ.$titleData->href;

                        // Fugly date parsing due to the fact my DNS-323 doesn't support php intl extension
                        $dateText = $titleTimestamp->innertext;
                        $dateText = substr($dateText, strpos($dateText,',')+1);
                        $dateText = str_replace($find, $replace, strtolower($dateText));
                        $date = strtotime($dateText); 
                        $item->timestamp = $date;

                        $item->name = "Maïa Mazaurette";
                        $elementText = $element->find('.text-container', 0);
                        // don't forget to replace images server url with gq one
                        foreach($elementText->find('img') as $image) {
                            $image->src = GQ.$image->src;
                        }
                        $item->content = $elementText->innertext;
                        $this->items[] = $item;
                    }
                    
                }
                
            }
        }
    }

    public function getName(){
        return 'Sexactu';
    }

    public function getURI(){
        return GQ.'/sexactu';
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
    public function getDescription(){
        return "Sexactu via rss-bridge";
    }
    
    public function correctCase($str) {
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
