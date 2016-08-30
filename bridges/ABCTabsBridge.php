<?php
class ABCTabsBridge extends BridgeAbstract{

	public $maintainer = "kranack";
	public $name = "ABC Tabs Bridge";
	public $uri = "http://www.abc-tabs.com/";
	public $description = "Returns 22 newest tabs";

	public function collectData(){
		$html = '';
        $html = $this->getSimpleHTMLDOM('http://www.abc-tabs.com/tablatures/nouveautes.html') or $this->returnClientError('No results for this query.');
		$table = $html->find('table#myTable', 0)->children(1);

		foreach ($table->find('tr') as $tab)
		{
		    $item = array();
		    $item['author'] = $tab->find('td', 1)->plaintext . ' - ' . $tab->find('td', 2)->plaintext;
		    $item['title'] = $tab->find('td', 1)->plaintext . ' - ' . $tab->find('td', 2)->plaintext;
		    $item['content'] = 'Le ' . $tab->find('td', 0)->plaintext . '<br> Par: ' . $tab->find('td', 5)->plaintext . '<br> Type: ' . $tab->find('td', 3)->plaintext;
		    $item['id'] = 'http://www.abc-tabs.com' . $tab->find('td', 2)->find('a', 0)->getAttribute('href');
		    $item['uri'] = 'http://www.abc-tabs.com' . $tab->find('td', 2)->find('a', 0)->getAttribute('href');
		    $this->items[] = $item;
		}
    }
}
