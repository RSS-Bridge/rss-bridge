<?php
class KununuBridge extends BridgeAbstract {
	const MAINTAINER = 'logmanoriginal';
	const NAME = 'Kununu Bridge';
	const URI = 'https://www.kununu.com/';
	const CACHE_TIMEOUT = 86400; // 24h
	const DESCRIPTION = 'Returns the latest reviews for a company and site of your choice.';

	const PARAMETERS = array(
		'global' => array(
			'site' => array(
				'name' => 'Site',
				'type' => 'list',
				'required' => true,
				'title' => 'Select your site',
				'values' => array(
					'Austria' => 'at',
					'Germany' => 'de',
					'Switzerland' => 'ch',
					'United States' => 'us'
				)
			),
			'full' => array(
				'name' => 'Load full article',
				'type' => 'checkbox',
				'required' => false,
				'exampleValue' => 'checked',
				'title' => 'Activate to load full article'
			)
		),
		array(
			'company' => array(
				'name' => 'Company',
				'required' => true,
				'exampleValue' => 'kununu-us',
				'title' => 'Insert company name (i.e. Kununu US) or URI path (i.e. kununu-us)'
			)
		)
	);

	private $companyName = '';

	public function getURI(){
		if(!is_null($this->getInput('company')) && !is_null($this->getInput('site'))) {

			$company = $this->fixCompanyName($this->getInput('company'));
			$site = $this->getInput('site');
			$section = '';

			switch($site) {
			case 'at':
			case 'de':
			case 'ch':
				$section = 'kommentare';
				break;
			case 'us':
				$section = 'reviews';
				break;
			}

			return self::URI . $site . '/' . $company . '/' . $section . '?sort=update_time_desc';
		}

		return parent::getURI();
	}

	public function getName(){
		if(!is_null($this->getInput('company'))) {
			$company = $this->fixCompanyName($this->getInput('company'));
			return ($this->companyName ?: $company) . ' - ' . self::NAME;
		}

		return parent::getName();
	}

	public function getIcon() {
		return 'https://www.kununu.com/favicon-196x196.png';
	}

	public function collectData(){
		$full = $this->getInput('full');

		// Load page
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Unable to receive data from ' . $this->getURI() . '!');

		$html = defaultLinkTo($html, static::URI);

		// Update name for this request
		$company = $html->find('span[class="company-name"]', 0)
			or returnServerError('Cannot find company name!');

		$this->companyName = $company->innertext;

		// Find the section with all the panels (reviews)
		$section = $html->find('section.kununu-scroll-element', 0)
			or returnServerError('Unable to find panel section!');

		// Find all articles (within the panels)
		$articles = $section->find('article')
			or returnServerError('Unable to find articles!');

		// Go through all articles
		foreach($articles as $article) {

			$anchor = $article->find('h1.review-title a', 0)
				or returnServerError('Cannot find article URI!');

			$date = $article->find('meta[itemprop=dateCreated]', 0)
				or returnServerError('Cannot find article date!');

			$rating = $article->find('span.rating', 0)
				or returnServerError('Cannot find article rating!');

			$summary = $article->find('[itemprop=name]', 0)
				or returnServerError('Cannot find article summary!');

			$item = array();

			$item['author'] = $this->extractArticleAuthorPosition($article);
			$item['timestamp'] = strtotime($date);
			$item['title'] = $rating->getAttribute('aria-label')
			. ' : '
			. strip_tags($summary->innertext);

			$item['uri'] = $anchor->href;

			if($full) {
				$item['content'] = $this->extractFullDescription($item['uri']);
			} else {
				$item['content'] = $this->extractArticleDescription($article);
			}

			$this->items[] = $item;

		}
	}

	/*
	* Returns a fixed version of the provided company name
	*/
	private function fixCompanyName($company){
		$company = trim($company);
		$company = str_replace(' ', '-', $company);
		$company = strtolower($company);

		$umlauts = Array('/ä/','/ö/','/ü/','/Ä/','/Ö/','/Ü/','/ß/');
		$replace = Array('ae','oe','ue','Ae','Oe','Ue','ss');

		return preg_replace($umlauts, $replace, $company);
	}

	/**
	* Returns the position of the author from a given article
	*/
	private function extractArticleAuthorPosition($article){
		// We need to parse the user-content manually
		$user_content = $article->find('div.user-content', 0)
			or returnServerError('Cannot find user content!');

		// Go through all h2 elements to find index of required span (I know... it's stupid)
		$author_position = 'Unknown';
		foreach($user_content->find('div') as $content) {
			if(stristr(strtolower($content->plaintext), 'position')) { /* This works for at, ch, de, us */
				$author_position = $content->next_sibling()->plaintext;
				break;
			}
		}

		return $author_position;
	}

	/**
	* Returns the description from a given article
	*/
	private function extractArticleDescription($article){
		$description = $article->find('[itemprop=reviewBody]', 0)
			or returnServerError('Cannot find article description!');

		return $description->innertext;
	}

	/**
	* Returns the full description from a given uri
	*/
	private function extractFullDescription($uri){
		// Load full article
		$html = getSimpleHTMLDOMCached($uri)
			or returnServerError('Could not load full description!');

		$html =	defaultLinkTo($html, static::URI);

		// Find the article
		$article = $html->find('article', 0)
			or returnServerError('Cannot find article!');

		// Luckily they use the same layout for the review overview and full article pages :)
		return $this->extractArticleDescription($article);
	}
}
