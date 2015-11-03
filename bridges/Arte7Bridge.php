<?php
/**
* RssBridgeArte7
*
* @name Arte +7
* @homepage http://www.arte.tv/
* @description Returns newest videos from ARTE +7
* @maintainer mitsukarenai
* @update 2015-10-31
* @use1(list|catfr="Toutes les vidéos (français)=>toutes-les-videos;Actu & société=>actu-société;Séries & fiction=>séries-fiction;Cinéma=>cinéma;Arts & spectacles classiques=>arts-spectacles-classiques;Culture pop=>culture-pop;Découverte=>découverte;Histoire=>histoire;Junior=>junior")
* @use2(list|catde="Alle Videos (deutsch)=>alle-videos;Aktuelles & Gesellschaft=>aktuelles-gesellschaft;Fernsehfilme & Serien=>fernsehfilme-serien;Kino=>kino;Kunst & Kultur=>kunst-kultur;Popkultur & Alternativ=>popkultur-alternativ;Entdeckung=>entdeckung;Geschichte=>geschichte;Junior=>junior")
*/
class Arte7Bridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Arte +7";
		$this->uri = "http://www.arte.tv/";
		$this->description = "Returns newest videos from ARTE +7";
		$this->update = "2015-10-31";
		$this->parameters["Catégorie (Français)"] =
		'[
			{
				"type" : "list",
				"identifier" : "catfr",
				"name" : "Catégorie",
				"values" : [
					{
						"name" : "Toutes les vidéos (français)",
						"value" : "toutes-les-videos"
					},
					{
						"name" : "Actu & société",
						"value" : "actu-société"
					},
					{
						"name" : "Séries & fiction",
						"value" : "séries-fiction"
					},
					{
						"name" : "Cinéma",
						"value" : "cinéma"
					}

				]


			}

		]';
	}


    public function collectData(array $param){

      function extractVideoset($category='toutes-les-videos', $lang='fr') 
         {
         $url = 'http://www.arte.tv/guide/'.$lang.'/plus7/'.$category;
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

      $category='toutes-les-videos'; $lang='fr';
      if (!empty($param['catfr']))
         $category=$param['catfr'];
      if (!empty($param['catde']))
         { $category=$param['catde']; $lang='de'; }
      $input_json = extractVideoset($category, $lang);

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
        return 'Arte7';
    }

    public function getURI(){
        return 'http://www.arte.tv/';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
