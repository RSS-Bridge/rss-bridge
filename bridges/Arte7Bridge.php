<?php
    class Arte7Bridge extends BridgeAbstract{

        const MAINTAINER = "mitsukarenai";
        const NAME = "Arte +7";
        const URI = "http://www.arte.tv/";
        const CACHE_TIMEOUT = 1800; // 30min
        const DESCRIPTION = "Returns newest videos from ARTE +7";
        const PARAMETERS = array(
            'Catégorie (Français)' => array(
                'catfr'=>array(
                    'type'=>'list',
                    'name'=>'Catégorie',
                    'values'=>array(
                        'Toutes les vidéos (français)'=>'toutes-les-videos',
                        'Actu & société'=>'actu-société',
                        'Séries & fiction'=>'séries-fiction',
                        'Cinéma'=>'cinéma',
                        'Arts & spectacles classiques'=>'arts-spectacles-classiques',
                        'Culture pop'=>'culture-pop',
                        'Découverte'=>'découverte',
                        'Histoire'=>'histoire',
                        'Junior'=>'junior'

                    )
                )
            ),
            'Catégorie (Allemand)' => array(
                'catde'=>array(
                    'type'=>'list',
                    'name'=>'Catégorie',
                    'values'=>array(
                        'Alle Videos (deutsch)'=>'alle-videos',
                        'Aktuelles & Gesellschaft'=>'aktuelles-gesellschaft',
                        'Fernsehfilme & Serien'=>'fernsehfilme-serien',
                        'Kino'=>'kino',
                        'Kunst & Kultur'=>'kunst-kultur',
                        'Popkultur & Alternativ'=>'popkultur-alternativ',
                        'Entdeckung'=>'entdeckung',
                        'Geschichte'=>'geschichte',
                        'Junior'=>'junior'
                    )
                )
            )
        );

    public function collectData(){

      switch($this->queriedContext){
      case 'Catégorie (Français)':
        $category=$this->getInput('catfr');
        $lang='fr';
        break;
      case 'Catégorie (Allemand)':
        $category=$this->getInput('catde');
        $lang='de';
        break;
      }

      $url = self::URI.'guide/'.$lang.'/plus7/'.$category;
      $input = getContents($url) or die('Could not request ARTE.');
      if(strpos($input, 'categoryVideoSet') !== FALSE){
        $input = explode('categoryVideoSet="', $input);
        $input = explode('}}', $input[1]);
        $input = $input[0].'}}';
      }else{
        $input = explode('videoSet="', $input);
        $input = explode('}]}', $input[1]);
        $input = $input[0].'}]}';
      }

      $input_json = json_decode(html_entity_decode($input, ENT_QUOTES), TRUE);

      foreach($input_json['videos'] as $element) {
            $item = array();
            $item['uri'] = str_replace("autoplay=1", "", $element['url']);
            $item['id'] = $element['id'];
               $hack_broadcast_time = $element['rights_end'];
               $hack_broadcast_time = strtok($hack_broadcast_time, 'T');
               $hack_broadcast_time = strtok('T');
            $item['timestamp'] = strtotime($element['scheduled_on'].'T'.$hack_broadcast_time);
            $item['title'] = $element['title'];
            if (!empty($element['subtitle']))
               $item['title'] = $element['title'].' | '.$element['subtitle'];
            $item['duration'] = round((int)$element['duration']/60);
            $item['content'] = $element['teaser'].'<br><br>'.$item['duration'].'min<br><a href="'.$item['uri'].'"><img src="' . $element['thumbnail_url'] . '" /></a>';
            $this->items[] = $item;
        }
    }

}
