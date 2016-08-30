<?php
class AllocineFRBridge extends BridgeAbstract{


    public $maintainer = "superbaillot.net";
    public $name = "Allo Cine Bridge";
    public $uri = "http://www.allocine.fr";
    public $description = "Bridge for allocine.fr";
    public $parameters = array( array(
        'category'=>array(
            'name'=>'category',
            'type'=>'list',
            'required'=>true,
            'exampleValue'=>'Faux Raccord',
            'title'=>'Select your category',
            'values'=>array(
                'Faux Raccord'=>'faux-raccord',
                'Top 5'=>'top-5',
                'Tueurs en Séries'=>'tueurs-en-serie'
            )
        )
    ));

    public function getURI(){
        switch($this->getInput('category')){
        case 'faux-raccord':
            $uri = 'http://www.allocine.fr/video/programme-12284/saison-24580/';
            break;
        case 'top-5':
            $uri = 'http://www.allocine.fr/video/programme-12299/saison-22542/';
            break;
        case 'tueurs-en-serie':
            $uri = 'http://www.allocine.fr/video/programme-12286/saison-22938/';
            break;
        }

        return $uri;
    }

    public function getName(){
        return $this->name.' : '
            .array_search(
                $this->getInput('category'),
                $this->parameters[$this->queriedContext]['category']['values']
            );
    }

    public function collectData(){

        $html = $this->getSimpleHTMLDOM($this->getURI())
            or $this->returnServerError("Could not request ".$this->getURI()." !");

        $category=array_search(
                $this->getInput('category'),
                $this->parameters[$this->queriedContext]['category']['values']
            );


        foreach($html->find('figure.media-meta-fig') as $element)
        {
            $item = array();

            $title = $element->find('div.titlebar h3.title a', 0);
            $content = trim($element->innertext);
            $figCaption = strpos($content, $category);

            if($figCaption !== false)
            {
                $content = str_replace('src="/', 'src="http://www.allocine.fr/', $content);
                $content = str_replace('href="/', 'href="http://www.allocine.fr/', $content);
                $content = str_replace('src=\'/', 'src=\'http://www.allocine.fr/', $content);
                $content = str_replace('href=\'/', 'href=\'http://www.allocine.fr/', $content);
                $item['content'] = $content;
                $item['title'] = trim($title->innertext);
                $item['uri'] = "http://www.allocine.fr" . $title->href;
                $this->items[] = $item;
            }
        }
    }

    public function getCacheDuration(){
        return 25200; // 7 hours
    }
}
