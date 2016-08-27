<?php
class KununuBridge extends BridgeAbstract{
	public $maintainer = "logmanoriginal";
	public $name = "Kununu Bridge"; /* This will be replaced later! */
	public $uri = "https://www.kununu.com"; /* This will be replaced later! */
	public $description = "Returns the latest reviews for a company and site of your choice.";

    public $parameters = array(
        'global' => array(
          'site'=>array(
            'name'=>'Site',
            'type'=>'list',
            'required'=>true,
            'exampleValue'=>'United States',
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

	public function collectData(){
        $params=$this->parameters[$this->queriedContext];

		// Get Site
		$site = strtolower(trim($params['site']['value']));
		if(!isset($site) || empty($site) || !$this->site_is_valid($site))
			$this->returnClientError('You must specify a valid site (&site=...)!');

		// Get Company (fixing whitespace and umlauts)
		$company = $this->encode_umlauts(strtolower(str_replace(' ', '-', trim($params['company']['value']))));
		if(!isset($company) || empty($company))
			$this->returnClientError('You must specify a company (&company=...)!');

		$full = false; // By default we'll load only short article
		if(isset($params['full']['value']))
			$full = strtolower(trim($params['full']['value']));

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
				$this->returnServerError('The reviews section is not defined for you selection!');
		}

		// Update URI for the content
		$this->uri .= "/{$site}/{$company}/{$section}";

		// Load page
		$html = $this->getSimpleHTMLDOM($this->uri);
		if($html === false)
			$this->returnServerError('Unable to receive data from ' . $this->uri . '!');

		// Update name for this request
		$this->name = $this->extract_company_name($html) . ' - ' . $this->name;

		// Find the section with all the panels (reviews)
		$section = $html->find('section.kununu-scroll-element', 0);
		if($section === false)
			$this->returnServerError('Unable to find panel section!');

		// Find all articles (within the panels)
		$articles = $section->find('article');
		if($articles === false || empty($articles))
			$this->returnServerError('Unable to find articles!');

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

	public function getCacheDuration(){
		return 86400; // 1 day
	}

	/**
	* Returns true if the given site is part of the parameters list
	*/
	private function site_is_valid($site){
		$parameter = $this->parameters['global'];
		$sites = $parameter['site']['values'];

		$site_names = array();

		foreach($sites as $name=>$value)
			$site_names[] = $value;

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
			$this->returnServerError('Cannot find panel for company name!');

		$company_name = $panel->find('h1', 0);
		if($company_name === false)
			$this->returnServerError('Cannot find company name!');

		return $company_name->plaintext;
	}

	/**
	* Returns the date from a given article
	*/
	private function extract_article_date($article){
		// They conviniently provide a time attribute for us :)
		$date = $article->find('time[itemprop=dtreviewed]', 0);
		if($date === false)
			$this->returnServerError('Cannot find article date!');

		return strtotime($date->datetime);
	}

	/**
	* Returns the rating from a given article
	*/
	private function extract_article_rating($article){
		$rating = $article->find('span.rating', 0);
		if($rating === false)
			$this->returnServerError('Cannot find article rating!');

		return $rating->getAttribute('aria-label');
	}

	/**
	* Returns the summary from a given article
	*/
	private function extract_article_summary($article){
		$summary = $article->find('[itemprop=summary]', 0);
		if($summary === false)
			$this->returnServerError('Cannot find article summary!');

		return strip_tags($summary->innertext);
	}

	/**
	* Returns the URI from a given article
	*/
	private function extract_article_uri($article){
		// Notice: This first part is the same as in extract_article_summary!
		$summary = $article->find('[itemprop=summary]', 0);
		if($summary === false)
			$this->returnServerError('Cannot find article summary!');

		$anchor = $summary->find('a', 0);
		if($anchor === false)
			$this->returnServerError('Cannot find article URI!');

		return 'https://www.kununu.com' . $anchor->href;
	}

	/**
	* Returns the position of the author from a given article
	*/
	private function extract_article_author_position($article){
		// We need to parse the aside manually
		$aside = $article->find('aside', 0);
		if($aside === false)
			$this->returnServerError('Cannot find article author information!');

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
			$this->returnServerError('Cannot find article description!');

		return $this->fix_url($description->innertext);
	}

	/**
	* Returns the full description from a given uri
	*/
	private function extract_full_description($uri){
		// Load full article
		$html = $this->getSimpleHTMLDOM($uri);
		if($html === false)
			$this->returnServerError('Could not load full description!');

		// Find the article
		$article = $html->find('article', 0);
		if($article === false)
			$this->returnServerError('Cannot find article!');

		// Luckily they use the same layout for the review overview and full article pages :)
		return $this->extract_article_description($article);
	}
}
