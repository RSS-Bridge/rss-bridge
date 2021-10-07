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
            $blockedbridges = array('Tester', 'Anime', 'Blizzard', 'Demo', 'Flickr');
            $bridgeerrors = array('exampleValue');
            if($this->strContainsArr($title, $blockedbridges)){
                continue;
            }
            $bridgestring = $this->getInput('url') . "/?action=display&format=Json&bridge="  . $element->find('input[name=bridge]',0)->value;
            $parameters = $this->getParametersFromBridge($element);
            if (empty($parameters)) {
                #$item['content'] = $bridgestring;
                $returnarray = $this->getBridgeFeed($bridgestring);
                $item['content'] = $returnarray[0];
                $item['categories'][] = $returnarray[1];
            } elseif ($this->strContainsArr($parameters, $bridgeerrors)){
                $item['content'] = $parameters;
                $item['categories'][] = 'missingparameter';
            }
            else {
                #$returnarray = $this->getBridgeFeed($bridgestring . $parameters);
                #$item['content'] = $returnarray[0];
                #$item['categories'][] = $returnarray[1];
                $item['content'] = $bridgestring . $parameters;
                $item['categories'][] = 'untested';
            }

			$this->items[] = $item;
		}
	}
    private function getParametersFromBridge($element){
        $paramstrings = array();
        $title = $element->find('h2', 0)->innertext;
        foreach( $element->getElementsByTagName('form') as $form ){
            $paramstring = '';
            foreach( $form->getElementsByTagName('div') as $parameter ){
                foreach( $parameter->getElementsByTagName('input') as $input ){
                    #if (!isset($input->required)) {
                    #    continue;
                    #}
                    switch ($input->type) {
                        case "number":
                            if (empty($input->placeholder)) {
                                if (empty($input->value)) {
                                    $errormsg = $errormsg . $title . ': No exampleValue or defaultValue for Numberfield "' . $input->name  . '"<br>';
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
                                    $errormsg = $errormsg . $title . ': No exampleValue or defaultValue for Textfield "' . $input->name  . '"<br>';
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
                    #if (!isset($select->required)) {
                    #    continue;
                    #}
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
        $returnarray = array();
        switch (count($feed->items)) {
            case 0:
                $returnarray[] = 'Bridge returns no items for url "' . $url . '"';
                $returnarray[] = 'broken';
                break;
            case 1:
                if (strpos($feed->items[0]->title, "Bridge returned error") !== false){
                    $returnarray[] =  $feed->items[0]->title;
                    $returnarray[] = 'broken';
                } else {
                    $returnarray[] =  'Bridge returns ' . count($feed->items) . ' items for url ' . $url;
                    $returnarray[] =  'working';
                }
                break;
            default:
                $returnarray[] =  'Bridge returns ' . count($feed->items) . ' items for url ' . $url;
                if (count($feed->items) >= 40) {
                    $returnarray[] =  'sizewarning';
                } else {
                    $returnarray[] =  'working';
                }
        }
        return $returnarray;
    }

    private function strContainsArr($str, array $arr)
    {
        foreach($arr as $a) {
            if (stripos($str,$a) !== false) return true;
        }
        return false;
    }   
}
