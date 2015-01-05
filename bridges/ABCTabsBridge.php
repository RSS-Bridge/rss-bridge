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
class ABCTabsBridge extends BridgeAbstract{
    
	private $request;
    
	public function collectData(array $param){
		$html = '';
        $html = file_get_html('http://www.abc-tabs.com/tablatures/nouveautes.html') or $this->returnError('No results for this query.', 404);
		$table = $html->find('table#myTable', 0)->children(1);
		
		foreach ($table->find('tr') as $tab)
		{
		    $item = new \Item();
		    $item->name = $tab->find('td', 1)->plaintext . ' - ' . $tab->find('td', 2)->plaintext;
		    $item->title = $tab->find('td', 1)->plaintext . ' - ' . $tab->find('td', 2)->plaintext;
		    $item->content = 'Le ' . $tab->find('td', 0)->plaintext . '<br> Par: ' . $tab->find('td', 5)->plaintext . '<br> Type: ' . $tab->find('td', 3)->plaintext;
		    $item->id = 'http://www.abc-tabs.com' . $tab->find('td', 2)->find('a', 0)->getAttribute('href');
		    $item->uri = 'http://www.abc-tabs.com' . $tab->find('td', 2)->find('a', 0)->getAttribute('href');
		    $this->items[] = $item;
		}
    }
	public function getName(){
		return 'ABC Tabs Bridge';
	}

	public function getURI(){
		return 'http://www.abc-tabs.com/';
	}

	public function getCacheDuration(){
		return 3600; // 1 hour
	}
}
