<?php
class WikipediaBridge extends BridgeAbstract{
	public function loadMetadatas(){
		$this->maintainer = 'logmanoriginal';
		$this->name = 'Wikipedia bridge for \'Today\'s featured article...\' for many languages';
		$this->uri = 'https://www.wikipedia.org/';
		$this->description = 'Returns \'Today\'s featured article...\' for a language of your choice';
		$this->update = '2016-08-07';

		$this->parameters[] = 
		'[
			{
				"name": "Language",
				"identifier": "language",
				"type": "list",
				"required": "true",
				"title": "Select your language",
				"exampleValue": "English",
				"values": [
					{
						"name": "English",
						"value": "en"
					},
					{
						"name": "German",
						"value": "de"	
					},
					{
						"name": "French",
						"value": "fr"
					},
					{
						"name": "Esperanto",
						"value": "eo"
					}
				]
			},
			{
				"name": "Load full article",
				"identifier": "fullarticle",
				"type": "checkbox",
				"required": "false",
				"title": "Activate to always load the full article",
				"exampleValue": "false"
			}
		]';
	}

	public function collectData(array $params){
		if(!isset($params['language']))
			$this->returnError('You must specify a valid language via \'&language=\'!', 400);
		
		if(!$this->CheckLanguageCode(strtolower($params['language'])))
			$this->returnError('The language code you provided (\'' . $params['language'] . '\') is not supported!', 400);
		
		$fullArticle = false;
		if(isset($params['fullarticle']))
			$fullArticle = $params['fullarticle'] === 'on' ? true : false;

		// We store the correct URI as URI of this bridge (so it can be used later!)
		$this->uri = 'https://' . strtolower($params['language']) . '.wikipedia.org';

		// While we at it let's also update the name for the feed
		$this->name = 'Today\'s featured articles from ' . strtolower($params['language']) . '.wikipedia.org';

		// This will automatically send us to the correct main page in any language (try it!)
		$html = $this->file_get_html($this->uri . '/wiki');

		if(!$html)
			$this->returnError('Could not load site: ' . $this->uri . '!', 404);

		/* 
		* Now read content depending on the language (make sure to create one function per language!)
		* We build the function name automatically, just make sure you create a private function ending
		* with your desired language code, where the language code is upper case! (en -> GetContentsEN).
		*/
		$function = 'GetContents' . strtoupper($params['language']);

		if(!method_exists($this, $function))
			$this->returnError('A function to get the contents for your langauage is missing (\'' . $function . '\')!', 501);
		
		/*
		* The method takes care of creating all items.
		*/
		$this->$function($html, $fullArticle);
	}

	/** 
	* Returns true if the language code is part of the parameters list
	*/
	private function CheckLanguageCode($languageCode){
		$parameter = json_decode($this->parameters[0], true);
		$languages = $parameter[0]['values'];

		$language_names = array();

		foreach($languages as $language)
			$language_names[] = $language['value'];
		
		return in_array($languageCode, $language_names);
	}

	/*
	* Adds a new item to $items using a generic operation (should work for most (all?) wikis)
	*/
	private function AddElementGeneric($element, $fullArticle){
		// Clean the bottom of the featured article
		$element->find('div', -1)->outertext = '';

		// The title and URI of the article is best defined in an anchor containint the string '...' ('full article ...')
		$target = $element->find('p/a', 0); // We'll use the first anchor as fallback
		foreach($element->find('//a') as $anchor){
			if(strpos($anchor->innertext, '...') !== false){
				$target = $anchor;
				break;
			} 
		}

		$item = new \Item();
		$item->uri = $this->uri . $target->href;
		$item->title = $target->title;

		if(!$fullArticle)
			$item->content = strip_tags(str_replace('href="/', 'href="' . $this->uri . '/', $element->innertext), '<a><p><br><img>');
		else 
			$item->content = $this->LoadFullArticle($item->uri);

		$this->items[] = $item;
	}

	/**
	* Loads the full article from a given URI
	*/
	private function LoadFullArticle($uri){
		$content_html = $this->file_get_html($uri);
		
		if(!$content_html)
			$this->returnError('Could not load site: ' . $uri . '!', 404);
		
		$content = $content_html->find('#mw-content-text', 0);

		if(!$content)
			$this->returnError('Could not find content in page: ' . $uri . '!', 404);
		
		// Let's remove a couple of things from the article
		$content->find('#toc', 0)->outertext = ''; // 'Contents' table

		foreach($content->find('ol.references') as $reference) // References
			$reference->outertext = '';

		return str_replace('href="/', 'href="' . $this->uri . '/', $content->innertext);
	}

	/**
	* Implementation for de.wikipedia.org
	*/
	private function GetContentsDE($html, $fullArticle){
		$element = $html->find('div[id=mf-tfa]', 0);
		$this->AddElementGeneric($element, $fullArticle);
	}

	/**
	* Implementation for fr.wikipedia.org
	*/
	private function GetContentsFR($html, $fullArticle){
		$element = $html->find('div[id=accueil-lumieresur]', 0);
		$this->AddElementGeneric($element, $fullArticle);
	}

	/**
	* Implementation for en.wikipedia.org
	*/
	private function GetContentsEN($html, $fullArticle){
		$element = $html->find('div[id=mp-tfa]', 0);
		$this->AddElementGeneric($element, $fullArticle);
	}

	/**
	* Implementation for eo.wikipedia.org
	*/
	private function GetContentsEO($html, $fullArticle){
		$element = $html->find('div[id=mf-artikolo-de-la-semajno]', 0);
		$this->AddElementGeneric($element, $fullArticle);
	}
}
