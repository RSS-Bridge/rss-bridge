<?php

class XPathBridge extends BridgeAbstract {
	const NAME = 'XPathBridge';
	const URI = 'https://github.com/rss-bridge/rss-bridge';
	const DESCRIPTION
		= 'Parse any webpage using <a href="https://devhints.io/xpath" target="_blank">XPath expressions</a>';
	const MAINTAINER = 'Niehztog';
	const PARAMETERS = array(
		'' => array(

			'url' => array(
				'name' => 'Enter web page URL',
				'title' => <<<"EOL"
You can specify any website URL which serves data suited for display in RSS feeds
(for example a news blog).
EOL
				, 'type' => 'text',
				'exampleValue' => 'https://news.blizzard.com/en-en',
				'defaultValue' => 'https://news.blizzard.com/en-en',
				'required' => true
			),

			'item' => array(
				'name' => 'Item selector',
				'title' => <<<"EOL"
Enter an XPath expression matching a list of dom nodes, each node containing one
feed article item in total (usually a surrounding &lt;div&gt; or &lt;span&gt; tag). This will
be the context nodes for all of the following expressions. This expression usually
starts with a single forward slash.
EOL
				, 'type' => 'text',
				'exampleValue' => '/html/body/div/div[4]/div[2]/div[2]/div/div/section/ol/li/article',
				'defaultValue' => '/html/body/div/div[4]/div[2]/div[2]/div/div/section/ol/li/article',
				'required' => true
			),

			'title' => array(
				'name' => 'Item title selector',
				'title' => <<<"EOL"
This expression should match a node contained within each article item node
containing the article headline. It should start with a dot followed by two
forward slashes, referring to any descendant nodes of the article item node.
EOL
				, 'type' => 'text',
				'exampleValue' => './/div/div[2]/h2',
				'defaultValue' => './/div/div[2]/h2',
				'required' => true
			),

			'content' => array(
				'name' => 'Item description selector',
				'title' => <<<"EOL"
This expression should match a node contained within each article item node
containing the article content or description. It should start with a dot
followed by two forward slashes, referring to any descendant nodes of the
article item node.
EOL
				, 'type' => 'text',
				'exampleValue' => './/div[@class="ArticleListItem-description"]/div[@class="h6"]',
				'defaultValue' => './/div[@class="ArticleListItem-description"]/div[@class="h6"]',
				'required' => false
			),

			'uri' => array(
				'name' => 'Item URL selector',
				'title' => <<<"EOL"
This expression should match a node's attribute containing the article URL
(usually the href attribute of an &lt;a&gt; tag). It should start with a dot
followed by two forward slashes, referring to any descendant nodes of
the article item node. Attributes can be selected by prepending an @ char
before the attributes name.
EOL
				, 'type' => 'text',
				'exampleValue' => './/a[@class="ArticleLink ArticleLink"]/@href',
				'defaultValue' => './/a[@class="ArticleLink ArticleLink"]/@href',
				'required' => false
			),

			'author' => array(
				'name' => 'Item author selector',
				'title' => <<<"EOL"
This expression should match a node contained within each article item
node containing the article author's name. It should start with a dot
followed by two forward slashes, referring to any descendant nodes of
the article item node.
EOL
				, 'type' => 'text',
				'required' => false
			),

			'timestamp' => array(
				'name' => 'Item date selector',
				'title' => <<<"EOL"
This expression should match a node or node's attribute containing the
article timestamp or date (parsable by PHP's strtotime function). It
should start with a dot followed by two forward slashes, referring to
any descendant nodes of the article item node. Attributes can be
selected by prepending an @ char before the attributes name.
EOL
				, 'type' => 'text',
				'exampleValue' => './/time[@class="ArticleListItem-footerTimestamp"]/@timestamp',
				'defaultValue' => './/time[@class="ArticleListItem-footerTimestamp"]/@timestamp',
				'required' => false
			),

			'enclosures' => array(
				'name' => 'Item image selector',
				'title' => <<<"EOL"
This expression should match a node's attribute containing an article
image URL (usually the src attribute of an &lt;img&gt; tag or a style
attribute). It should start with a dot followed by two forward slashes,
referring to any descendant nodes of the article item node. Attributes
can be selected by prepending an @ char before the attributes name.
EOL
				, 'type' => 'text',
				'exampleValue' => './/div[@class="ArticleListItem-image"]/@style',
				'defaultValue' => './/div[@class="ArticleListItem-image"]/@style',
				'required' => false
			),

			'categories' => array(
				'name' => 'Item category selector',
				'title' => <<<"EOL"
This expression should match a node or node's attribute contained
within each article item node containing the article category. This
could be inside &lt;div&gt; or &lt;span&gt; tags or sometimes be hidden
in a data attribute. It should start with a dot followed by two
forward slashes, referring to any descendant nodes of the article
item node. Attributes can be selected by prepending an @ char
before the attributes name.
EOL
				, 'type' => 'text',
				'exampleValue' => './/div[@class="ArticleListItem-label"]',
				'defaultValue' => './/div[@class="ArticleListItem-label"]',
				'required' => false
			),

			'fix_encoding' => array(
				'name' => 'Fix encoding',
				'title' => <<<"EOL"
Check this to fix feed encoding by invoking PHP's utf8_decode
function on all extracted texts. Try this in case you see "broken" or
"weird" characters in your feed where you'd normally expect umlauts
or any other non-ascii characters.
EOL
				, 'type' => 'checkbox',
				'required' => false
			),

		)
	);
	const CACHE_TIMEOUT = 3600;

	private $feedName = self::NAME;
	private $feedUri = self::URI;
	private $feedIcon = '';

	public function collectData() {

		$this->feedUri = $this->encodeUri($this->getInput('url'));
		$xpathItem = urldecode($this->getInput('item'));

		$webPageHtml = new DOMDocument();
		libxml_use_internal_errors(true);
		$webPageHtml->loadHTML(getContents($this->feedUri));
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		$xpath = new DOMXPath($webPageHtml);

		$name = $xpath->query('.//title');
		if(count($name) === 1) {
			$this->feedName = $this->getItemValueOrNodeValue($name);
		}

		$icon = $xpath->query('.//link[@rel="icon"]/@href');
		if(count($icon) === 1) {
			$this->feedIcon = $this->cleanImageUrl($this->getItemValueOrNodeValue($icon));
		}

		$entries = @$xpath->query($xpathItem);
		if($entries === false) {
			return;
		}

		foreach ($entries as $entry) {
			$item = array();
			foreach(array('title', 'content', 'uri', 'author', 'timestamp', 'enclosures', 'categories') as $param) {

				$expression = urldecode($this->getInput($param));
				if('' === $expression) {
					continue;
				}

				//can be a string or DOMNodeList, depending on the expression result
				$typedResult = @$xpath->evaluate($expression, $entry);
				if ($typedResult === false || ($typedResult instanceof DOMNodeList && count($typedResult) !== 1)
					|| (is_string($typedResult) && strlen(trim($typedResult)) === 0)) {
					continue;
				}

				$item[$param] = $this->formatParamValue($param, $this->getItemValueOrNodeValue($typedResult));

			}
			$this->items[] = $item;
		}

	}

	public function getName(){
		return $this->feedName;
	}

	public function getURI() {
		return $this->feedUri;
	}

	public function getIcon() {
		if($this->feedIcon) {
			return $this->feedIcon;
		}
		return '';
	}

	/**
	 * @param $param
	 * @param $value
	 * @return array|false|int|string|string[]|void[]
	 */
	private function formatParamValue($param, $value)
	{
		switch ($param) {
			case 'uri':
				return urljoin($this->feedUri, $value);
			case 'timestamp':
				return strtotime($value);
			case 'enclosures':
				return array($this->cleanImageUrl($value));
			case 'categories':
				return array($this->fixEncoding($value));
		}
		return $this->fixEncoding($value);
	}

	/**
	 * @param $imageUrl
	 * @return string|void
	 */
	private function cleanImageUrl($imageUrl)
	{
		$result = preg_match('~(?:http(?:s)?:)?[\/a-zA-Z0-9\-_\.]+\.(?:jpg|gif|png|jpeg|ico){1}~', $imageUrl, $matches);
		if(1 !== $result) {
			return;
		}
		return urljoin($this->feedUri, $matches[0]);
	}

	/**
	 * @param $typedResult
	 * @return string
	 */
	private function getItemValueOrNodeValue($typedResult)
	{
		if($typedResult instanceof DOMNodeList) {
			$item = $typedResult->item(0);
			if ($item instanceof DOMElement) {
				return trim($item->nodeValue);
			} elseif ($item instanceof DOMAttr) {
				return trim($item->value);
			}
		} elseif(is_string($typedResult) && strlen($typedResult) > 0) {
			return trim($typedResult);
		}
	}

	private function fixEncoding($input)
	{
		return $this->getInput('fix_encoding') ? utf8_decode($input) : $input;
	}

	private function encodeUri($uri)
	{
		if (strpos($uri, 'https%3A%2F%2F') === 0
			|| strpos($uri, 'http%3A%2F%2F') === 0) {
			$uri = urldecode($uri);
		}

		$uri = str_replace('|', '%7C', $uri);

		return $uri;
	}
}
