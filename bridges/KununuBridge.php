<?php
class KununuBridge extends BridgeAbstract{
	public function loadMetadatas(){
		$this->maintainer = "logmanoriginal";
		$this->name = "Kununu Bridge"; /* This will be replaced later! */
		$this->uri = "https://www.kununu.com"; /* This will be replaced later! */
		$this->description = "Returns the latest reviews for a company and site of your choice.";
		$this->update = "2016-08-15";

		$this->parameters['global'] = 
		'[
			{
				"name" : "Site",
				"identifier" : "site",
				"type" : "list",
				"required" : true,
				"exampleValue" : "United States",
				"title" : "Select your site",
				"values" : 
				[
					{
						"name" : "Austria",
						"value" : "at"
					},
					{
						"name" : "Germany",
						"value" : "de"
					},
					{
						"name" : "Switzerland",
						"value" : "ch"
					},
					{
						"name" : "United States",
						"value" : "us"
					}
				]
			},
			{
				"name" : "Load full article",
				"identifier" : "full",
				"type" : "checkbox",
				"required" : false,
				"exampleValue" : "checked",
				"title" : "Activate to load full article"
			}
		]';

		$this->parameters[] = 
		'[
			{
				"name" : "Company",
				"identifier" : "company",
				"type" : "text",
				"required" : true,
				"exampleValue" : "kununu-us",
				"title" : "Insert company name (i.e. Kununu US) or URI path (i.e. kununu-us)"
			}
		]';
	}

	public function collectData(array $params){

		// Get Site
		$site = strtolower(trim($params['site']));
		if(!isset($site) || empty($site) || !$this->site_is_valid($site))
			$this->returnError('You must specify a valid site (&site=...)!', 400);

		// Get Company (fixing whitespace and umlauts)
		$company = $this->encode_umlauts(strtolower(str_replace(' ', '-', trim($params['company']))));
		if(!isset($company) || empty($company))
			$this->returnError('You must specify a company (&company=...)!', 400);

		$full = false; // By default we'll load only short article
		if(isset($params['full']))
			$full = strtolower(trim($params['full'])) === 'on';

		// Get reviews section name (depends on site)
		$section = '';
		switch($site){
			case 'at':
			case 'de':
			case 'ch':
				$section = 'kommentare';
				break;
			case 'us':
				$section = 'reviews';
				break;
			default:
				$this->returnError('The reviews section is not defined for you selection!', 404);
		}

		// Update URI for the content
		$this->uri .= "/{$site}/{$company}/{$section}";

		// Load page
		$html = $this->file_get_html($this->uri);
		if($html === false)
			$this->returnError('Unable to receive data from ' . $this->uri . '!', 404);

		// Update name for this request
		$this->name = $this->extract_company_name($html) . ' - ' . $this->name;

		// Find the section with all the panels (reviews)
		$section = $html->find('section.kununu-scroll-element', 0);
		if($section === false)
			$this->returnError('Unable to find panel section!', 404);

		// Find all articles (within the panels)
		$articles = $section->find('article');
		if($articles === false || empty($articles))
			$this->returnError('Unable to find articles!', 404);

		// Go through all articles
		foreach($articles as $article){
			$item = new \Item();

			$item->author = $this->extract_article_author_position($article);
			$item->timestamp = $this->extract_article_date($article);
			$item->title = $this->extract_article_rating($article) . ' : ' . $this->extract_article_summary($article);
			$item->uri = $this->extract_article_uri($article);

			if($full)
				$item->content = $this->extract_full_description($item->uri);
			else
				$item->content = $this->extract_article_description($article);

			$this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 86400; // 1 day
	}

	/** 
	* Returns true if the given site is part of the parameters list
	*/
	private function site_is_valid($site){
		$parameter = json_decode($this->parameters['global'], true);
		$sites = $parameter[0]['values'];

		$site_names = array();

		foreach($sites as $site_item)
			$site_names[] = $site_item['value'];

		return in_array($site, $site_names);
	}

	/**
	* Fixes relative URLs in the given text
	*/
	private function fix_url($text){
		return preg_replace('/href=(\'|\")\//i', 'href="https://www.kununu.com/', $text);
	}

	/**
	* Encodes unmlauts in the given text
	*/
	private function encode_umlauts($text){
		$umlauts = Array("/ä/","/ö/","/ü/","/Ä/","/Ö/","/Ü/","/ß/");
		$replace = Array("ae","oe","ue","Ae","Oe","Ue","ss");

		return preg_replace($umlauts, $replace, $text);
	}

	/**
	* Returns the company name from the review html
	*/
	private function extract_company_name($html){
		$panel = $html->find('div.panel', 0);
		if($panel === false)
			$this->returnError('Cannot find panel for company name!', 404);
		
		$company_name = $panel->find('h1', 0);
		if($company_name === false)
			$this->returnError('Cannot find company name!', 404);
		
		return $company_name->plaintext;
	}

	/**
	* Returns the date from a given article
	*/
	private function extract_article_date($article){
		// They conviniently provide a time attribute for us :)
		$date = $article->find('time[itemprop=dtreviewed]', 0);
		if($date === false)
			$this->returnError('Cannot find article date!', 404);
		
		return strtotime($date->datetime);
	}

	/**
	* Returns the rating from a given article
	*/
	private function extract_article_rating($article){
		$rating = $article->find('span.rating', 0);
		if($rating === false)
			$this->returnError('Cannot find article rating!', 404);
		
		return $rating->getAttribute('aria-label');
	}

	/**
	* Returns the summary from a given article
	*/
	private function extract_article_summary($article){
		$summary = $article->find('[itemprop=summary]', 0);
		if($summary === false)
			$this->returnError('Cannot find article summary!', 404);
		
		return strip_tags($summary->innertext);
	}

	/**
	* Returns the URI from a given article
	*/
	private function extract_article_uri($article){
		// Notice: This first part is the same as in extract_article_summary!
		$summary = $article->find('[itemprop=summary]', 0);
		if($summary === false)
			$this->returnError('Cannot find article summary!', 404);

		$anchor = $summary->find('a', 0);
		if($anchor === false)
			$this->returnError('Cannot find article URI!', 404);
		
		return 'https://www.kununu.com' . $anchor->href;
	}

	/**
	* Returns the position of the author from a given article
	*/
	private function extract_article_author_position($article){
		// We need to parse the aside manually
		$aside = $article->find('aside', 0);
		if($aside === false)
			$this->returnError('Cannot find article author information!', 404);

		// Go through all h2 elements to find index of required span (I know... it's stupid)
		$author_position = 'Unknown';
		foreach($aside->find('h2') as $subject){
			if(stristr(strtolower($subject->plaintext), 'position')){ /* This works for at, ch, de, us */
				$author_position = $subject->next_sibling()->plaintext;
				break;
			}
		}
		
		return $author_position;
	}

	/**
	* Returns the description from a given article
	*/
	private function extract_article_description($article){
		$description = $article->find('div[itemprop=description]', 0);
		if($description === false)
			$this->returnError('Cannot find article description!', 404);
		
		return $this->fix_url($description->innertext);
	}

	/**
	* Returns the full description from a given uri
	*/
	private function extract_full_description($uri){
		// Load full article
		$html = file_get_html($uri);
		if($html === false)
			$this->returnError('Could not load full description!', 404);

		// Find the article
		$article = $html->find('article', 0);
		if($article === false)
			$this->returnError('Cannot find article!', 404);

		// Luckily they use the same layout for the review overview and full article pages :)
		return $this->extract_article_description($article);
	}
}
