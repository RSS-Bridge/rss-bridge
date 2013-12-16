<?php
/**
* RssBridgeOpenClassrooms
* Retrieve lastest tutorials from OpenClassrooms.
* Returns the most recent tutorials, sorting by date (most recent first).
*
* @name OpenClassrooms Bridge
* @description Returns latest tutorials from OpenClassrooms.
* @use1(u="informatique or sciences")
*/
class OpenClassroomsBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
        $link = 'http://fr.openclassrooms.com/'.$param[u].'/cours?title=&sort=updatedAt+desc';

        $html = file_get_html($link) or $this->returnError('Could not request OpenClassrooms.', 404);

        foreach($html->find('li.col6') as $element) {
                $item = new \Item();
                $item->uri = 'http://fr.openclassrooms.com'.$element->find('a', 0)->href;
                $item->title = $element->find('div.courses-content strong', 0)->innertext;
                $item->content = $element->find('span.course-tags', 0)->innertext;
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'OpenClassrooms';
    }

    public function getURI(){
        return 'http://fr.openclassrooms.com';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
