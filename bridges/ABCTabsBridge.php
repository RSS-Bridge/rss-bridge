<?php
class ABCTabsBridge extends BridgeAbstract{

	private $request;

    public function loadMetadatas() {

		$this->maintainer = "kranack";
		$this->name = "ABC Tabs Bridge";
		$this->uri = "http://www.abc-tabs.com/";
		$this->description = "Returns 22 newest tabs";
		$this->update = "2016-08-09";

	}

	public function collectData(array $param){
		$html = '';
        $html = $this->file_get_html('http://www.abc-tabs.com/tablatures/nouveautes.html') or $this->returnError('No results for this query.', 404);
		$table = $html->find('table#myTable', 0)->children(1);
		
		foreach ($table->find('tr') as $tab)
		{
		    $item = new \Item();
		    $item->author = $tab->find('td', 1)->plaintext . ' - ' . $tab->find('td', 2)->plaintext;
		    $item->title = $tab->find('td', 1)->plaintext . ' - ' . $tab->find('td', 2)->plaintext;
		    $item->content = 'Le ' . $tab->find('td', 0)->plaintext . '<br> Par: ' . $tab->find('td', 5)->plaintext . '<br> Type: ' . $tab->find('td', 3)->plaintext;
		    $item->id = 'http://www.abc-tabs.com' . $tab->find('td', 2)->find('a', 0)->getAttribute('href');
		    $item->uri = 'http://www.abc-tabs.com' . $tab->find('td', 2)->find('a', 0)->getAttribute('href');
		    $this->items[] = $item;
		}
    }
}
