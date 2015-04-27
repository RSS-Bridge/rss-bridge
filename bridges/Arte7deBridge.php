<?php
/**
* RssBridgeArte7de
* Returns images from given page and tags
* 2014-05-25
*
* @name Arte +7 DE
* @homepage http://www.arte.tv/guide/de/
* @description Returns newest videos from ARTE +7 (german)
* @maintainer mitsukarenai
*/
class Arte7deBridge extends BridgeAbstract{

    public function collectData(array $param){

	$input_json = json_decode(file_get_contents('http://www.arte.tv/guide/de/plus7.json'), TRUE) or $this->returnError('Could not request ARTE.', 404);
   
        foreach($input_json['videos'] as $element) {
            $item = new \Item();
            $item->uri = $element['url'];
            $item->postid = $item->uri;

				$date = $element['airdate_long'];
				$date = explode(' ', $date);
				$day = (int)$date['1'];
				$month=FALSE;
				switch ($date['2']) {
					case 'Januar':
						$month=1;break;
					case 'Februar':
						$month=2;break;
					case 'März':
						$month=3;break;
					case 'April':
						$month=4;break;
					case 'Mai':
						$month=5;break;
					case 'Juni':
						$month=6;break;
					case 'Juli':
						$month=7;break;
					case 'August':
						$month=8;break;
					case 'September':
						$month=9;break;
					case 'Oktober':
						$month=10;break;
					case 'November':
						$month=11;break;
					case 'Dezember':
						$month=12;break;
					}
				$year=(int)date('Y');
				$heure=explode(':', $date['4']);
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
        return 'Arte7de';
    }

    public function getURI(){
        return 'http://www.arte.tv/';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
