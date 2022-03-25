<?php
class GlassdoorBridge extends BridgeAbstract {

	// Contexts
	const CONTEXT_BLOG	 = 'Blogs';
	const CONTEXT_REVIEW = 'Company Reviews';
	const CONTEXT_GLOBAL = 'global';

	// Global context parameters
	const PARAM_LIMIT = 'limit';

	// Blog context parameters
	const PARAM_BLOG_TYPE = 'blog_type';
	const PARAM_BLOG_FULL = 'full_article';

	const BLOG_TYPE_HOME			 = 'Home';
	const BLOG_TYPE_COMPANIES_HIRING = 'Companies Hiring';
	const BLOG_TYPE_CAREER_ADVICE	 = 'Career Advice';
	const BLOG_TYPE_INTERVIEWS		 = 'Interviews';
	const BLOG_TYPE_GUIDE			 = 'Guides';

	// Review context parameters
	const PARAM_REVIEW_COMPANY = 'company';

	const MAINTAINER = 'logmanoriginal';
	const NAME = 'Glassdoor Bridge';
	const URI = 'https://www.glassdoor.com/';
	const DESCRIPTION = 'Returns feeds for blog posts and company reviews';
	const CACHE_TIMEOUT = 86400; // 24 hours

	const PARAMETERS = array(
		self::CONTEXT_BLOG => array(
			self::PARAM_BLOG_TYPE => array(
				'name' => 'Blog type',
				'type' => 'list',
				'title' => 'Select the blog you want to follow',
				'values' => array(
					self::BLOG_TYPE_HOME				=> 'blog/',
					self::BLOG_TYPE_COMPANIES_HIRING	=> 'blog/companies-hiring/',
					self::BLOG_TYPE_CAREER_ADVICE		=> 'blog/career-advice/',
					self::BLOG_TYPE_INTERVIEWS			=> 'blog/interviews/',
					self::BLOG_TYPE_GUIDE				=> 'blog/guide/'
				)
			),
			self::PARAM_BLOG_FULL => array(
				'name' => 'Full article',
				'type' => 'checkbox',
				'title' => 'Enable to return the full article for each post'
			),
		),
		self::CONTEXT_REVIEW => array(
			self::PARAM_REVIEW_COMPANY => array(
				'name' => 'Company URL',
				'type' => 'text',
				'required' => true,
				'title' => 'Paste the company review page URL here!',
				'exampleValue' => 'https://www.glassdoor.com/Reviews/GitHub-Reviews-E671945.htm'
			)
		),
		self::CONTEXT_GLOBAL => array(
			self::PARAM_LIMIT => array(
				'name' => 'Limit',
				'type' => 'number',
				'defaultValue' => -1,
				'title' => 'Specifies the maximum number of items to return (default: All)'
			)
		)
	);

	private $host = self::URI; // They redirect without notice :/
	private $title = '';

	public function getURI() {
		switch($this->queriedContext) {
			case self::CONTEXT_BLOG:
				return self::URI . $this->getInput(self::PARAM_BLOG_TYPE);
			case self::CONTEXT_REVIEW:
				return $this->filterCompanyURI($this->getInput(self::PARAM_REVIEW_COMPANY));
		}

		return parent::getURI();
	}

	public function getName() {
		return $this->title ? $this->title . ' - ' . self::NAME : parent::getName();
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI());

		$this->host = $html->find('link[rel="canonical"]', 0)->href;

		$html = defaultLinkTo($html, $this->host);

		$this->title = $html->find('meta[property="og:title"]', 0)->content;
		$limit = $this->getInput(self::PARAM_LIMIT);

		switch($this->queriedContext) {
			case self::CONTEXT_BLOG:
				$this->collectBlogData($html, $limit);
				break;
			case self::CONTEXT_REVIEW:
				$this->collectReviewData($html, $limit);
				break;
		}
	}

	private function collectBlogData($html, $limit) {
		$posts = $html->find('section')
			or returnServerError('Unable to find blog posts!');

		foreach($posts as $post) {
			$item = array();

			$item['uri'] = $post->find('header a', 0)->href;
			$item['title'] = $post->find('header', 0)->plaintext;
			$item['content'] = $post->find('div[class="excerpt-content"]', 0)->plaintext;
			$item['enclosures'] = array(
				$this->getFullSizeImageURI($post->find('div[class*="post-thumb"]', 0)->{'data-original'})
			);

			// optionally load full articles
			if($this->getInput(self::PARAM_BLOG_FULL)) {
				$full_html = getSimpleHTMLDOMCached($item['uri']);

				$full_html = defaultLinkTo($full_html, $this->host);

				$item['author'] = $full_html->find('a[rel="author"]', 0);
				$item['content'] = $full_html->find('article', 0);
				$item['timestamp'] = strtotime($full_html->find('time.updated', 0)->datetime);
				$item['categories'] = $full_html->find('span[class="post_tag"]');
			}

			$this->items[] = $item;

			if($limit > 0 && count($this->items) >= $limit)
				return;
		}
	}

	private function collectReviewData($html, $limit) {
		$reviews = $html->find('#ReviewsFeed li[id^="empReview]')
			or returnServerError('Unable to find reviews!');

		foreach($reviews as $review) {
			$item = array();

			$item['uri'] = $review->find('a.reviewLink', 0)->href;
			$item['title'] = $review->find('[class="summary"]', 0)->plaintext;
			$item['author'] = $review->find('div.author span', 0)->plaintext;
			$item['timestamp'] = strtotime($review->find('time', 0)->datetime);

			$mainText = $review->find('p.mainText', 0)->plaintext;

			$description = '';
			foreach($review->find('div.description p') as $p) {

				if ($p->hasClass('strong')) {
					$p->tag = 'strong';
					$p->removeClass('strong');
				}

				$description .= $p;

			}

			$item['content'] = "<p>{$mainText}</p><p>{$description}</p>";

			$this->items[] = $item;

			if($limit > 0 && count($this->items) >= $limit)
				return;
		}
	}

	private function getFullSizeImageURI($uri) {
		/* Images are scaled for display on the website. The scaling takes place
		 * on the host, who provides images in different sizes.
		 *
		 * For example:
		 * https://www.glassdoor.com/blog/app/uploads/sites/2/GettyImages-982402074-e1538092065712-390x193.jpg
		 *
		 * By removing the size information we receive the full sized image.
		 *
		 * For example:
		 * https://www.glassdoor.com/blog/app/uploads/sites/2/GettyImages-982402074-e1538092065712.jpg
		 */

		$uri = filter_var($uri, FILTER_SANITIZE_URL);
		return preg_replace('/(.*)(\-\d+x\d+)(\.jpg)/', '$1$3', $uri);
	}

	private function filterCompanyURI($uri) {
		/* Make sure the URI is a valid review page. Unfortunately there is no
		 * simple way to determine if the URI is valid, because of automagic
		 * redirection and strange naming conventions.
		 */
		if(!filter_var($uri,
			FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
			returnClientError('The specified URL is invalid!');
		}

		$uri = filter_var($uri, FILTER_SANITIZE_URL);
		$path = parse_url($uri, PHP_URL_PATH);
		$parts = explode('/', $path);

		$allowed_strings = array(
			'de-DE' => 'Bewertungen',
			'en-AU' => 'Reviews',
			'nl-BE' => 'Reviews',
			'fr-BE' => 'Avis',
			'en-CA' => 'Reviews',
			'fr-CA' => 'Avis',
			'fr-FR' => 'Avis',
			'en-IN' => 'Reviews',
			'en-IE' => 'Reviews',
			'nl-NL' => 'Reviews',
			'de-AT' => 'Bewertungen',
			'de-CH' => 'Bewertungen',
			'fr-CH' => 'Avis',
			'en-GB' => 'Reviews',
			'en'	=> 'Reviews'
		);

		if(!in_array($parts[1], $allowed_strings)) {
			returnClientError('Please specify a URL pointing to the companies review page!');
		}

		return $uri;
	}
}
