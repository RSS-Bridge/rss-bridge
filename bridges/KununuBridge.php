<?php
class KununuBridge extends BridgeAbstract {
	const MAINTAINER = "logmanoriginal";
	const NAME = "Kununu Bridge";
	const URI = "https://www.kununu.com/";
	const CACHE_TIMEOUT = 86400; // 24h
	const DESCRIPTION = "Returns the latest reviews for a company and site of your choice.";

	const PARAMETERS = array(
		'global' => array(
			'site'=>array(
				'name'=>'Site',
				'type'=>'list',
				'required'=>true,
				'title'=>'Select your site',
				'values'=>array(
					'Austria'=>'at',
					'Germany'=>'de',
					'Switzerland'=>'ch',
					'United States'=>'us'
				)
		),
			'full'=>array(
				'name'=>'Load full article',
				'type'=>'checkbox',
				'required'=>false,
				'exampleValue'=>'checked',
				'title'=>'Activate to load full article'
			)
		),

		array(
			'company'=>array(
				'name'=>'Company',
				'required'=>true,
				'exampleValue'=>'kununu-us',
				'title'=>'Insert company name (i.e. Kununu US) or URI path (i.e. kununu-us)'
			)
		)
	);

	private $companyName = '';

	public function getURI(){
		if(!is_null($this->getInput('company')) && !is_null($this->getInput('site'))){

			$company = $this->fix_company_name($this->getInput('company'));
			$site = $this->getInput('site');
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
			}

			return self::URI . $site . '/' . $company . '/' . $section;
		}

		return parent::getURI();
	}

	function getName(){
		if(!is_null($this->getInput('company'))){
			$company = $this->fix_company_name($this->getInput('company'));
			return ($this->companyName?:$company).' - '.self::NAME;
		}

		return paren::getName();
	}

	public function collectData(){
		$full = $this->getInput('full');

		// Load page
		$html = getSimpleHTMLDOMCached($this->getURI());
		if(!$html)
			returnServerError('Unable to receive data from ' . $this->getURI() . '!');
		// Update name for this request
		$this->companyName = $this->extract_company_name($html);

		// Find the section with all the panels (reviews)
		$section = $html->find('section.kununu-scroll-element', 0);
		if($section === false)
			returnServerError('Unable to find panel section!');

		// Find all articles (within the panels)
		$articles = $section->find('article');
		if($articles === false || empty($articles))
			returnServerError('Unable to find articles!');

		// Go through all articles
		foreach($articles as $article){
			$item = array();

			$item['author'] = $this->extract_article_author_position($article);
			$item['timestamp'] = $this->extract_article_date($article);
			$item['title'] = $this->extract_article_rating($article) . ' : ' . $this->extract_article_summary($article);
			$item['uri'] = $this->extract_article_uri($article);

			if($full)
				$item['content'] = $this->extract_full_description($item['uri']);
			else
				$item['content'] = $this->extract_article_description($article);

			$this->items[] = $item;
		}
	}

	/**
	* Fixes relative URLs in the given text
	*/
	private function fix_url($text){
		return preg_replace('/href=(\'|\")\//i', 'href="'.self::URI, $text);
	}

	/*
	* Returns a fixed version of the provided company name
	*/
	private function fix_company_name($company){
		$company = trim($company);
		$company = str_replace(' ', '-', $company);
		$company = strtolower($company);
		return $this->encode_umlauts($company);
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
		$company_name = $html->find('h1[itemprop=name]', 0);
		if(is_null($company_name))
			returnServerError('Cannot find company name!');

		return $company_name->plaintext;
	}

	/**
	* Returns the date from a given article
	*/
	private function extract_article_date($article){
		// They conviniently provide a time attribute for us :)
		$date = $article->find('meta[itemprop=dateCreated]', 0);
		if(is_null($date))
			returnServerError('Cannot find article date!');

		return strtotime($date->content);
	}

	/**
	* Returns the rating from a given article
	*/
	private function extract_article_rating($article){
		$rating = $article->find('span.rating', 0);
		if(is_null($rating))
			returnServerError('Cannot find article rating!');

		return $rating->getAttribute('aria-label');
	}

	/**
	* Returns the summary from a given article
	*/
	private function extract_article_summary($article){
		$summary = $article->find('[itemprop=name]', 0);
		if(is_null($summary))
			returnServerError('Cannot find article summary!');

		return strip_tags($summary->innertext);
	}

	/**
	* Returns the URI from a given article
	*/
	private function extract_article_uri($article){
		$anchor = $article->find('ku-company-review-more', 0);
		if(is_null($anchor))
			returnServerError('Cannot find article URI!');

		return self::URI . $anchor->{'review-url'};
	}

	/**
	* Returns the position of the author from a given article
	*/
	private function extract_article_author_position($article){
		// We need to parse the user-content manually
		$user_content = $article->find('div.user-content', 0);
		if(is_null($user_content))
			returnServerError('Cannot find user content!');

		// Go through all h2 elements to find index of required span (I know... it's stupid)
		$author_position = 'Unknown';
		foreach($user_content->find('div') as $content){
			if(stristr(strtolower($content->plaintext), 'position')){ /* This works for at, ch, de, us */
				$author_position = $content->next_sibling()->plaintext;
				break;
			}
		}

		return $author_position;
	}

	/**
	* Returns the description from a given article
	*/
	private function extract_article_description($article){
		$description = $article->find('[itemprop=reviewBody]', 0);
		if(is_null($description))
			returnServerError('Cannot find article description!');

		return $this->fix_url($description->innertext);
	}

	/**
	* Returns the full description from a given uri
	*/
	private function extract_full_description($uri){
		// Load full article
		$html = getSimpleHTMLDOMCached($uri);
		if($html === false)
			returnServerError('Could not load full description!');

		// Find the article
		$article = $html->find('article', 0);
		if(is_null($article))
			returnServerError('Cannot find article!');

		// Luckily they use the same layout for the review overview and full article pages :)
		return $this->extract_article_description($article);
	}
}
