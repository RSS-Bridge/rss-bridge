<?php
class Arte7Bridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Arte +7";
		$this->uri = "http://www.arte.tv/";
		$this->description = "Returns newest videos from ARTE +7";
		$this->update = "2016-08-09";
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
					},
					{
						"name" : "Arts & spectacles classiques",
						"value" : "arts-spectacles-classiques"
					},
					{
						"name" : "Culture pop",
						"value" : "culture-pop"
					},
					{
						"name" : "Découverte",
						"value" : "découverte"
					},
					{
						"name" : "Histoire",
						"value" : "histoire"
					},
					{
						"name" : "Junior",
						"value" : "junior"
					}

				]


			}

		]';
		$this->parameters["Catégorie (Allemand)"] =
		'[
			{
				"type" : "list",
				"identifier" : "catde",
				"name" : "Catégorie",
				"values" : [
					{
						"name" : "Alle Videos (deutsch)",
						"value" : "alle-videos"
					},
					{
						"name" : "Aktuelles & Gesellschaft",
						"value" : "aktuelles-gesellschaft"
					},
					{
						"name" : "Fernsehfilme & Serien",
						"value" : "fernsehfilme-serien"
					},
					{
						"name" : "Kino",
						"value" : "kino"
					},
					{
						"name" : "Kunst & Kultur",
						"value" : "kunst-kultur"
					},
					{
						"name" : "Popkultur & Alternativ",
						"value" : "popkultur-alternativ"
					},
					{
						"name" : "Entdeckung",
						"value" : "entdeckung"
					},
					{
						"name" : "Geschichte",
						"value" : "geschichte"
					},
					{
						"name" : "Junior",
						"value" : "junior"
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
            $item->uri = str_replace("autoplay=1", "", $element['url']);
            $item->id = $element['id'];
               $hack_broadcast_time = $element['rights_end'];
               $hack_broadcast_time = strtok($hack_broadcast_time, 'T');
               $hack_broadcast_time = strtok('T');
            $item->timestamp = strtotime($element['scheduled_on'].'T'.$hack_broadcast_time);
            $item->title = $element['title'];
            if (!empty($element['subtitle']))
               $item->title = $element['title'].' | '.$element['subtitle'];
            $item->duration = round((int)$element['duration']/60);
            $item->content = $element['teaser'].'<br><br>'.$item->duration.'min<br><a href="'.$item->uri.'"><img src="' . $element['thumbnail_url'] . '" /></a>';
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
