<?php
/**
* RssBridgeOpenClassrooms
* Retrieve lastest tutorials from OpenClassrooms.
* Returns the most recent tutorials, sorting by date (most recent first).
*
* @name OpenClassrooms Bridge
* @homepage https://openclassrooms.com/
* @description Returns latest tutorials from OpenClassrooms.
* @maintainer sebsauvage
* @update 2015-10-30
* @use1(list|u="Arts & Culture=>arts;Code=>code;Design=>design;Entreprise=>business;Numérique=>digital;Sciences=>sciences;Sciences humaines=>humanities;Systèmes d'information=>it;Autres=>others")
*/
class OpenClassroomsBridge extends BridgeAbstract{

    public function collectData(array $param){
        if (empty($param['u']))
        {
            $this->returnError('Error: You must chose a category.', 404);
        }
    
        $html = '';
        $link = 'https://openclassrooms.com/courses?categories='.$param['u'].'&title=&sort=updatedAt+desc';

        $html = file_get_html($link) or $this->returnError('Could not request OpenClassrooms.', 404);

        foreach($html->find('.courseListItem') as $element) {
                $item = new \Item();
                $item->uri = 'https://openclassrooms.com'.$element->find('a', 0)->href;
                $item->title = $element->find('h3', 0)->plaintext;
                $item->content = $element->find('slidingItem__descriptionContent', 0)->plaintext;
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'OpenClassrooms';
    }

    public function getURI(){
        return 'https://openclassrooms.com/';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
