<?php
class KununuBridge extends HttpCachingBridgeAbstract {
	const MAINTAINER = "logmanoriginal";
	const NAME = "Kununu Bridge";
	const URI = "https://www.kununu.com/";
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

    private $companyName='';

    public function getURI(){
        $company = $this->encode_umlauts(strtolower(str_replace(' ', '-', trim($this->getInput('company')))));
        $site=$this->getInput('site');
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

        return self::URI.$site.'/'.$company.'/'.$section;
    }

    function getName(){
        $company = $this->encode_umlauts(strtolower(str_replace(' ', '-', trim($this->getInput('company')))));
        return  ($this->companyName?:$company).' - '.self::NAME;
    }

	public function collectData(){
        $full = $this->getInput('full');

		// Load page
		$html = $this->getSimpleHTMLDOM($this->getURI());
		if(!$html)
			$this->returnServerError('Unable to receive data from ' . $this->getURI() . '!');
		// Update name for this request
		$this->companyName = $this->extract_company_name($html);

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
	* Fixes relative URLs in the given text
	*/
	private function fix_url($text){
		return preg_replace('/href=(\'|\")\//i', 'href="'.self::URI, $text);
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

		return self::URI . $anchor->href;
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
		if($this->get_cached_time($uri) <= strtotime('-24 hours'))
			$this->remove_from_cache($uri);

		$html = $this->get_cached($uri);
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
