<?php
class OpenClassroomsBridge extends BridgeAbstract{

	const MAINTAINER = "sebsauvage";
	const NAME = "OpenClassrooms Bridge";
	const URI = "https://openclassrooms.com/";
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = "Returns latest tutorials from OpenClassrooms.";

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'Catégorie',
            'type'=>'list',
            'required'=>true,
            'values'=>array(
                'Arts & Culture'=>'arts',
                'Code'=>'code',
                'Design'=>'design',
                'Entreprise'=>'business',
                'Numérique'=>'digital',
                'Sciences'=>'sciences',
                'Sciences Humaines'=>'humainities',
                'Systèmes d\'information'=>'it',
                'Autres'=>'others'
            )
        )
    ));

    public function getURI(){
      return self::URI.'/courses?categories='.$this->getInput('u').'&'
        .'title=&sort=updatedAt+desc';
    }

    public function collectData(){
        $html = getSimpleHTMLDOM($this->getURI())
          or returnServerError('Could not request OpenClassrooms.');

        foreach($html->find('.courseListItem') as $element) {
                $item = array();
                $item['uri'] = self::URI.$element->find('a', 0)->href;
                $item['title'] = $element->find('h3', 0)->plaintext;
                $item['content'] = $element->find('slidingItem__descriptionContent', 0)->plaintext;
                $this->items[] = $item;
        }
    }
}
