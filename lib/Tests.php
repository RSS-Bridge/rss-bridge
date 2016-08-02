<?php

require_once 'RssBridge.php';


Bridge::setDir('bridges/');
Format::setDir('formats/');
Cache::setDir('caches/');

//Setting up everything before testing

abstract class BridgeTest extends PHPUnit_Framework_TestCase {


    //Workaround for allowing phpUnit to run multiple tests at the same time.
    protected $preserveGlobalState = FALSE;

    protected function prepareTemplate(\Text_Template $template) {
        $template->setVar(array(
            'iniSettings' => '',
            'constants' => '',
            'included_files' => '',
            'globals' => '$GLOBALS[\'__PHPUNIT_BOOTSTRAP\'] = ' . var_export($GLOBALS['__PHPUNIT_BOOTSTRAP'], TRUE) . ";\n",
        ));
    }

    
	public $bridgeName = '';
	public $parameters;

    public $bridgeDatas = null;

    private static $argNumber = 0;

    public static function setUpBeforeClass() {

        PHPUnit_Framework_Error_Warning::$enabled = FALSE;
        PHPUnit_Framework_Error_Notice::$enabled = FALSE;

		ini_set('default_socket_timeout', 30);
		
		BridgeTest::$argNumber = 0;
    }
    
    public function setUp() {
        
        $parameters = [];
        
        if(method_exists($this, "loadParameters")) {

            $parameters = $this->loadParameters()[BridgeTest::$argNumber];

        }

        $tested_bridge = Bridge::create($this->bridgeName);

        $tested_bridge->setDatas($parameters);    
        $this->bridgeDatas = $tested_bridge->getDatas();

        BridgeTest::$argNumber++;
        
	}

	const TEST_TITLE = 0;
	const TEST_TIMESTAMP = 1;
	const TEST_CONTENT = 2;
	const TEST_ID = 3;
	const TEST_URI = 4;
	const TEST_NAME = 5;

	public function defaultTest($datas, $parameters, $minElementCount = -1) {

		$this->assertNotEmpty($datas, "The bridge is returning empty datas");
		$this->assertGreaterThan($minElementCount, count($datas), "There is not enough elements in the bridge");

		foreach($datas as $row) {
			if(in_array(self::TEST_TITLE, $parameters)) {
	            $this->assertNotEmpty($row->title, "A row hasn't got a title !");
			}
			if(in_array(self::TEST_TIMESTAMP, $parameters)) {
		        $this->assertNotNull($row->timestamp, "A row is missing a timestamp.");
		        $this->assertNotEquals($row->timestamp, 0, "A row has an invalid timestamp");
			}
			if(in_array(self::TEST_CONTENT, $parameters)) {
	            $this->assertNotEmpty($row->content, "A row doesn't have content !");
			}
			if(in_array(self::TEST_ID, $parameters)) {
            	$this->assertNotNull($row->id, "A row hasn't got an ID !");
			}
			if(in_array(self::TEST_URI, $parameters)) {
	            $this->assertNotEmpty($row->uri, "A row is missing an URI");
			}
			if(in_array(self::TEST_NAME, $parameters)) {
            	$this->assertNotEmpty($row->name, "A row hasn't got a name !");
			}
		}

	}

}
