<?php
class TesterBridge extends FeedExpander {
    
	const MAINTAINER = 'bockiii';
	const NAME = 'Tester Bridge';
	const URI = 'https://google.com';
	const CACHE_TIMEOUT = 4800; //2hours
	const DESCRIPTION = 'Tests bridges';
    const PARAMETERS = array(
		array(
			'url' => array(
				'name' => 'RSSBridge Url',
				'required' => true,
				'title' => 'Paste your instance URL',
				'exampleValue' => 'https://feed.eugenemolotov.ru'
			)
		)
	);

	public function collectData(){
        $html = getSimpleHTMLDOM($this->getInput('url'))
        or returnServerError('could not request ' . $this->getInput('url'));

        foreach($html->find('section[id]') as $element) {
            $item = array();
            $title = $element->find('h2', 0)->innertext;
            $item['title'] = $title;
            $blockedbridges = array('Tester', 'Anime', 'Blizzard');
            $bridgeerrors = array('RSS-Bridge-Error');
            if($this->strContainsArr($title, $blockedbridges)){
                continue;
            }
            $bridgestring = $this->getInput('url') . "/?action=display&format=Json&bridge="  . $element->find('input[name=bridge]',0)->value;
            $parameters = $this->getParametersFromBridge($element);
            if (empty($parameters)) {
                $item['content'] = $bridgestring;
                #$item['content'] = 'Amount of items: ' . $this->getBridgeFeed($bridgestring);
            } elseif ($this->strContainsArr($parameters, $bridgeerrors)){
                $item['content'] = $parameters;
            }
            else {
                $item['content'] = $bridgestring . $parameters;
            }

			$this->items[] = $item;
		}
	}
    private function getParametersFromBridge($element){
        $paramstrings = array();
        foreach( $element->getElementsByTagName('form') as $form ){
            $paramstring = '';
            #$paramstring = $paramstring . $form . ' NEXT ';
            foreach( $form->getElementsByTagName('div') as $parameter ){
                #$paramstring = $paramstring . $parameter . ' NEXT ';
                foreach( $parameter->getElementsByTagName('input') as $input ){
                    switch ($input->type) {
                        case "number":
                            if (empty($input->placeholder)) {
                                if (empty($input->value)) {
                                    $errormsg = $errormsg . 'RSS-Bridge-Error: Number ' . $input->name  . ' contains no example or default value<br>';
                                    #$value = "0";
                                } else {
                                    $value = $input->value;
                                }
                            } else {
                                $value = $input->placeholder;
                            }
                            $paramstring = $paramstring . '&' . $input->name . '=' . $value;
                            break;
                        case "text":
                            if (empty($input->placeholder)){
                                if (empty($input->value)){
                                    $errormsg = $errormsg . 'RSS-Bridge-Error: Text ' . $input->name  . ' contains no example or default value<br>';
                                    #$value = "FillMe";
                                } else {
                                    $value = $input->value;
                                }
                            } else {
                                $value = $input->placeholder;
                            }
                            $paramstring = $paramstring . '&' . $input->name . '=' . $value;
                            break;
                        case "checkbox":
                            if (isset($input->checked)){
                                $paramstring = $paramstring . '&' . $input->name . '=on';
                            }
                            break;
                        default:
                    }
                }
                foreach( $parameter->getElementsByTagName('select') as $select ){
                    $value = '';
                    foreach($select->getElementsByTagName('option') as $option) {
                        if (isset($option->selected)) {
                            $value = $option->value;
                        }
                    }
                    if (empty($value)) {
                        $value = $select->getElementsByTagName('option')[0]->value;
                    }
                    $paramstring = $paramstring . '&' . $select->name . '=' . $value;
                }
            }
            $paramstrings[] = $paramstring;
        }
        if (isset($errormsg)) {
            return $errormsg;
        }
        return $paramstrings[array_rand($paramstrings)];
    }

    private function getBridgeFeed($url){
        $html = getContents($url)
            or returnServerError('Could not request ' . $url);
        $feed = json_decode($html);
        return count($feed->items);
    }

    private function strContainsArr($str, array $arr)
    {
        foreach($arr as $a) {
            if (stripos($str,$a) !== false) return true;
        }
        return false;
    }
    
}
