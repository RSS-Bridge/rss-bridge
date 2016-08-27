<?php
class DemoBridge extends BridgeAbstract{

    public $maintainer = "teromene";
    public $name = "DemoBridge";
    public $uri = "http://github.com/sebsauvage/rss-bridge";
    public $description = "Bridge used for demos";

    public $parameters = array(
        'testCheckbox' => array(
            'testCheckbox'=>array(
                'type'=>'checkbox',
                'name'=>'test des checkbox'
            )
        ),

        'testList' => array(
            'testList'=>array(
                'type'=>'list',
                'name'=>'test des listes',
                'values'=>array(
                    'Test'=>'test',
                    'Test 2'=>'test2'
                )
            )
        ),

        'testNumber' => array(
            'testNumber'=>array(
                'type'=>'number',
                'name'=>'test des numéros',
                'exampleValue'=>'1515632'
            )
        )
    );

    public function collectData(){

        $item = array();
        $item['author'] = "Me!";
        $item['title'] = "Test";
        $item['content'] = "Awesome content !";
        $item['id'] = "Lalala";
        $item['uri'] = "http://test.test/test";

        $this->items[] = $item;

    }

    public function getCacheDuration(){
        return 00; // 1 hour
    }
}
