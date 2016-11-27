<?php
class DemoBridge extends BridgeAbstract{

    const MAINTAINER = "teromene";
    const NAME = "DemoBridge";
    const URI = "http://github.com/rss-bridge/rss-bridge";
    const DESCRIPTION = "Bridge used for demos";

    const PARAMETERS = array(
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
        $item['uri'] = "http://example.com/test";

        $this->items[] = $item;

    }
}
