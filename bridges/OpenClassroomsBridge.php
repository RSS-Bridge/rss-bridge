<?php
class OpenClassroomsBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "sebsauvage";
		$this->name = "OpenClassrooms Bridge";
		$this->uri = "https://openclassrooms.com/";
		$this->description = "Returns latest tutorials from OpenClassrooms.";
		$this->update = '2016-08-17';


		$this->parameters[] =
		'[
			{
				"name" : "Catégorie",
				"identifier" : "u",
				"type" : "list",
				"values" : [
					{
						"name" : "Arts & Culture",
						"value" : "arts"
					},
					{
						"name" : "Code",
						"value" : "code"
					},
					{
						"name" : "Design",
						"value" : "design"
					},
					{
						"name" : "Entreprise",
						"value" : "business"
					},
					{
						"name" : "Numérique",
						"value" : "digital"
					},
					{
						"name" : "Sciences",
						"value" : "sciences"
					},
					{
						"name" : "Sciences Humaines",
						"value" : "humainities"
					},
					{
						"name" : "Systèmes d\'information",
						"value" : "it"
					},
					{
						"name" : "Autres",
						"value" : "others"
					}
				]
			}
		]';
	}


    public function collectData(array $param){
        if (empty($param['u']))
        {
            $this->returnServerError('Error: You must chose a category.');
        }

        $html = '';
        $link = 'https://openclassrooms.com/courses?categories='.$param['u'].'&title=&sort=updatedAt+desc';

        $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request OpenClassrooms.');

        foreach($html->find('.courseListItem') as $element) {
                $item = new \Item();
                $item->uri = 'https://openclassrooms.com'.$element->find('a', 0)->href;
                $item->title = $element->find('h3', 0)->plaintext;
                $item->content = $element->find('slidingItem__descriptionContent', 0)->plaintext;
                $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
