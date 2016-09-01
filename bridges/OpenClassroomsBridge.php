<?php
class OpenClassroomsBridge extends BridgeAbstract{

	public $maintainer = "sebsauvage";
	public $name = "OpenClassrooms Bridge";
	public $uri = "https://openclassrooms.com/";
	public $description = "Returns latest tutorials from OpenClassrooms.";

    public $parameters = array( array(
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
      return $this->uri.'/courses?categories='.$this->getInput('u').'&'
        .'title=&sort=updatedAt+desc';
    }

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->getURI())
          or $this->returnServerError('Could not request OpenClassrooms.');

        foreach($html->find('.courseListItem') as $element) {
                $item = array();
                $item['uri'] = $this->uri.$element->find('a', 0)->href;
                $item['title'] = $element->find('h3', 0)->plaintext;
                $item['content'] = $element->find('slidingItem__descriptionContent', 0)->plaintext;
                $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
