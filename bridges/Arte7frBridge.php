<?php
/**
* RssBridgeArte7fr
* Returns images from given page and tags
* 2014-05-25
*
* @name Arte +7 FR
* @homepage http://www.arte.tv/guide/fr/
* @description Returns newest videos from ARTE +7 (french)
* @maintainer mitsukarenai
*/
class Arte7frBridge extends BridgeAbstract{

    public function collectData(array $param){

	$input_json = json_decode(file_get_contents('http://www.arte.tv/guide/fr/plus7.json'), TRUE) or $this->returnError('Could not request ARTE.', 404);
   
        foreach($input_json['videos'] as $element) {
            $item = new \Item();
            $item->uri = 'http://www.arte.tv'.$element['url'];
            $item->postid = $item->uri;

				$date = $element['airdate_long'];
				$date = explode(' ', $date);
				$day = (int)$date['1'];
				$month=FALSE;
				switch ($date['2']) {
					case 'janvier':
						$month=1;break;
					case 'février':
						$month=2;break;
					case 'mars':
						$month=3;break;
					case 'avril':
						$month=4;break;
					case 'mai':
						$month=5;break;
					case 'juin':
						$month=6;break;
					case 'juillet':
						$month=7;break;
					case 'août':
						$month=8;break;
					case 'septembre':
						$month=9;break;
					case 'octobre':
						$month=10;break;
					case 'novembre':
						$month=11;break;
					case 'décembre':
						$month=12;break;
					}
				$year=(int)date('Y');
				$heure=explode('h', $date['4']);
				$hour=(int)$heure['0'];
				$minute=(int)$heure['1'];
  

            $item->timestamp = mktime($hour, $minute, 0, $month, $day, $year);
            $item->thumbnailUri = $element['image_url'];
            $item->title = $element['title'];
            $item->content = $element['desc'].'<br><br>'.$element['video_channels'].', '.$element['duration'].'min<br><img src="' . $item->thumbnailUri . '" />';
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
