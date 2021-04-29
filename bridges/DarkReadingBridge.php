<?php
class DarkReadingBridge extends FeedExpander {
	const MAINTAINER = 'ORelio';
	const NAME = 'Dark Reading Bridge';
	const URI = 'https://www.darkreading.com/';
	const DESCRIPTION = 'Returns the newest articles from Dark Reading';

	const PARAMETERS = array( array(
		'feed' => array(
			'name' => 'Feed',
			'type' => 'list',
			'values' => array(
				'All Dark Reading Stories' => '000_AllArticles',
				'Attacks/Breaches' => '644_Attacks/Breaches',
				'Application Security' => '645_Application%20Security',
				'Database Security' => '646_Database%20Security',
				'Cloud' => '647_Cloud',
				'Endpoint' => '648_Endpoint',
				'Authentication' => '649_Authentication',
				'Privacy' => '650_Privacy',
				'Mobile' => '651_Mobile',
				'Perimeter' => '652_Perimeter',
				'Risk' => '653_Risk',
				'Compliance' => '654_Compliance',
				'Operations' => '655_Operations',
				'Careers and People' => '656_Careers%20and%20People',
				'Identity and Access Management' => '657_Identity%20and%20Access%20Management',
				'Analytics' => '658_Analytics',
				'Threat Intelligence' => '659_Threat%20Intelligence',
				'Security Monitoring' => '660_Security%20Monitoring',
				'Vulnerabilities / Threats' => '661_Vulnerabilities%20/%20Threats',
				'Advanced Threats' => '662_Advanced%20Threats',
				'Insider Threats' => '663_Insider%20Threats',
				'Vulnerability Management' => '664_Vulnerability%20Management',
			)
		)
	));

	public function collectData(){
		$feed = $this->getInput('feed');
		$feed_splitted = explode('_', $feed);
		$feed_id = $feed_splitted[0];
		$feed_name = $feed_splitted[1];
		if(empty($feed) || !ctype_digit($feed_id) || !preg_match('/[A-Za-z%20\/]/', $feed_name)) {
			returnClientError('Invalid feed, please check the "feed" parameter.');
		}
		$feed_url = $this->getURI() . 'rss_simple.asp';
		if ($feed_id != '000') {
			$feed_url .= '?f_n=' . $feed_id . '&f_ln=' . $feed_name;
		}
		$this->collectExpandableDatas($feed_url);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		if (empty($item['content']))
			return null; //ignore dummy articles
		$article = getSimpleHTMLDOMCached($item['uri'])
			or returnServerError('Could not request Dark Reading: ' . $item['uri']);
		$item['content'] = $this->extractArticleContent($article);
		$item['enclosures'] = array(); //remove author profile picture
		return $item;
	}

	private function extractArticleContent($article){
		$content = $article->find('div#article-main', 0)->innertext;

		foreach (array(
			'<div class="divsplitter',
			'<div style="float: left; margin-right: 2px;',
			'<div class="more-insights',
			'<div id="more-insights',
		) as $div_start) {
			$content = stripRecursiveHTMLSection($content, 'div', $div_start);
		}

		$content = stripWithDelimiters($content, '<h1 ', '</h1>');

		return $content;
	}
}
