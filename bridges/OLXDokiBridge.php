<?php



class OLXDokiBridge extends BridgeAbstract
{
   const MAINTAINER = 'RedTyper';
    const NAME = 'OLXNow - Truck Damaged';
    const URI = 'https://www.olx.pl/';
    const CACHE_TIMEOUT = 0;
    const DESCRIPTION = 'Push Notification from new ads on OLX';

    public function collectData(){
        $html = getSimpleHTMLDOM(self::URI . 'motoryzacja/dostawcze-ciezarowe/dostawcze/?search%5Bfilter_enum_mark%5D%5B0%5D=ford&search%5Bfilter_enum_mark%5D%5B1%5D=peugeot&search%5Bfilter_enum_mark%5D%5B2%5D=renault&search%5Bfilter_enum_mark%5D%5B3%5D=iveco&search%5Bfilter_float_price%3Ato%5D=25000&search%5Bfilter_float_year%3Afrom%5D=2007');

        $limit = 0;

        
          foreach($html->find('table#offers_table > tbody > tr > td > div > table') as $element) {
  
                $title = $element->find('tbody > tr', 0)->find('td', 1)->find('strong', 0)->plaintext;
                $uri = $element->find('tbody > tr', 0)->find('td', 1)->find('a[href]', 0)->href;
                $price = $element->find('tbody > tr', 0)->find('td', 2)->plaintext;
                $images = $element->find('tbody > tr', 0)->find('td', 0)->find('img', 0)->src;
                $location = $element->find('tbody > tr', 1)->find('td', 0)->find('p > small', 0)->plaintext;
                $time = $element->find('tbody > tr', 1)->find('td', 0)->find('p > small', 1)->plaintext;
                $content = 'Cena:' . $price . '<br />Lokalizacja:' . $location . '<br />Dodano:' . $time . '<br /><img src="' . $images .'">';
                
                $item = array();
                $item['timestamp'] = time();
                $item['title'] = $title;                            
                $item['uri'] = $uri;
                $item['price'] = $price;
                $item['image'] = $images;
                $item['location'] = $location;
                $item['content'] = $content;


                $this->items[] = $item;
                           
        }
    }

    
 
}



  
