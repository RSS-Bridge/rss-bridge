<?php
/**
* ABCTabsBridge
* Returns the newest tabs
*
* @name ABC Tabs Bridge
* @homepage http://www.abc-tabs.com/
* @description Returns 22 newest tabs
* @maintainer kranack
* @update 2014-07-23
*
*/
class DemoBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "teromene";
		$this->name = "DemoBridge";
		$this->uri = "http://github.com/sebsauvage/rss-bridge";
		$this->description = "Bridge used for demos";
		$this->update = "2015-11-03";

		$this->parameters['testCheckbox'] =
		'[
			{
				"type" : "checkbox",
				"identifier" : "testCheckbox",
				"name" : "test des checkbox"
			}

		]';

		$this->parameters['testList'] =
		'[
			{
				"type" : "list",
				"identifier" : "testList",
				"name" : "test des listes",
				"values" : [
					{
						"name" : "Test",
						"value" : "test"
					},
					{
						"name" : "Test 2",
						"value" : "test2"
					}
				]
			}
		]';
		$this->parameters['testNumber'] =
		'[
			{
				"type" : "number",
				"identifier" : "testNumber",
				"name" : "test des numéros",
				"exampleValue" : "1515632"

			}

		]';

	}

	public function collectData(array $param){

    }

	public function getCacheDuration(){
		return 3600; // 1 hour
	}
}
