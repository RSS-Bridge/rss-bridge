<?php
/**
* RssBridgeArte7fr
* Returns images from given page and tags
*
* @name Arte +7 FR
* @homepage http://www.arte.tv/guide/fr/plus7
* @description Returns newest videos from ARTE +7 (french)
* @maintainer mitsukarenai
* @update 2015-10-30
* @use1(list|cat="Toutes les vidéos=>toutes-les-videos;Actu & société=>actu-société;Séries & fiction=>séries-fiction;Cinéma=>cinéma;Arts & spectacles classiques=>arts-spectacles-classiques;Culture pop=>culture-pop;Découverte=>découverte;Histoire=>histoire;Junior=>junior")
*/
class Arte7frBridge extends BridgeAbstract{

    public function collectData(array $param){

      function extractVideoset($category='toutes-les-videos') 
         {
         $url = 'http://www.arte.tv/guide/fr/plus7/'.$category;
         $input = file_get_contents($url) or die('Could not request ARTE.');
         if(strpos($input, 'categoryVideoSet') !== FALSE)
            {
            $input = explode('categoryVideoSet: ', $input);
            $input = explode('}},', $input[1]);
            $input = $input[0].'}}';
            }
         else
            {
            $input = explode('videoSet: ', $input);
            $input = explode('}]},', $input[1]);
            $input = $input[0].'}]}';
            }
         $input = json_decode($input, TRUE);
         return $input;
         }

      $category='toutes-les-videos';
      if (!empty($param['cat']))
         $category=$param['cat'];
      $input_json = extractVideoset($category);

      foreach($input_json['videos'] as $element) {
            $item = new \Item();
            $item->uri = $element['url'];
            $item->id = $element['id'];
               $hack_broadcast_time = $element['rights_end'];
               $hack_broadcast_time = strtok($hack_broadcast_time, 'T');
               $hack_broadcast_time = strtok('T');
            $item->timestamp = strtotime($element['scheduled_on'].'T'.$hack_broadcast_time);
            $item->thumbnailUri = $element['thumbnail_url'];
            $item->title = $element['title'];
            if (!empty($element['subtitle']))
               $item->title = $element['title'].' | '.$element['subtitle'];
            $item->duration = round((int)$element['duration']/60);
            $item->content = $element['teaser'].'<br><br>'.$item->duration.'min<br><a href="'.$item->uri.'"><img src="' . $item->thumbnailUri . '" /></a>';
            $this->items[] = $item;
        }
    }

    public function getName(){
        return 'Arte7fr';
    }

    public function getURI(){
        return 'http://www.arte.tv/';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
