<?php
class AllocineFRBridge extends BridgeAbstract{

    public function loadMetadatas() {

        $this->maintainer = "superbaillot.net";
        $this->name = "Allo Cine Bridge";
        $this->uri = "http://www.allocine.fr";
        $this->description = "Bridge for allocine.fr";
        $this->update = '2016-08-17';

        $this->parameters[] = 
        '[
            {
                "name" : "category",
                "identifier" : "category",
                "type" : "list",
                "required" : true,
                "exampleValue" : "Faux Raccord",
                "title" : "Select your category",
                "values" : 
                [
                    {
                        "name" : "Faux Raccord",
                        "value" : "faux-raccord"
                    },
                    {
                        "name" : "Top 5",
                        "value" : "top-5"
                    },
                    {
                        "name" : "Tueurs En Serie",
                        "value" : "tuers-en-serie"
                    }
                ]
            }
        ]';
    }

    public function collectData(array $params){

        // Check all parameters
        if(!isset($params['category']))
            $this->returnClientError('You must specify a valid category (&category= )!');

        $category = '';
        switch($params['category']){
            case 'faux-raccord':
                $this->uri = 'http://www.allocine.fr/video/programme-12284/saison-24580/';
                $category = 'Faux Raccord';
                break;
            case 'top-5':
                $this->uri = 'http://www.allocine.fr/video/programme-12299/saison-22542/';
                $category = 'Top 5';
                break;
            case 'tuers-en-serie':
                $this->uri = 'http://www.allocine.fr/video/programme-12286/saison-22938/';
                $category = 'Tueurs en Séries';
                break;
            default:
                $this->returnClientError('You must select a valid category!');
        }

        // Update bridge name to match selection
        $this->name .= ' : ' . $category;

        $html = $this->file_get_html($this->uri) or $this->returnServerError("Could not request {$this->uri}!");

        foreach($html->find('figure.media-meta-fig') as $element)
        {
            $item = new Item();
            
            $title = $element->find('div.titlebar h3.title a', 0);
            $content = trim($element->innertext);
            $figCaption = strpos($content, $category);

            if($figCaption !== false)
            {
                $content = str_replace('src="/', 'src="http://www.allocine.fr/', $content);
                $content = str_replace('href="/', 'href="http://www.allocine.fr/', $content);
                $content = str_replace('src=\'/', 'src=\'http://www.allocine.fr/', $content);
                $content = str_replace('href=\'/', 'href=\'http://www.allocine.fr/', $content);
                $item->content = $content;
                $item->title = trim($title->innertext);
                $item->uri = "http://www.allocine.fr" . $title->href;
                $this->items[] = $item;
            }
        }
    }

    public function getCacheDuration(){
        return 25200; // 7 hours
    }
}
