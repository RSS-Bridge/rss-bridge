<?php

/**
 * An abstract class for easily creating abstract user-defined bridges
 *
 * This class serves as boilerplate for abstract classes that aim for high
 * potential for inheritance and customization. It contains expressions for each
 * bridge parameter and methods to override how they are used. See UserDefinedAbstract
 * for an example of how to use this bridge.
 *
 **/
abstract class UserDefinedAbstract extends BridgeAbstract {

	/**
	 * Source URL
	 * You can specify any website URL which serves data suited for display in RSS feeds
	 * (for example a news blog).
	 *
	 * Use {@see UserDefinedAbstract::getSourceUrl()} to read this parameter
	 */
	const FEED_SOURCE_URL = '';

	/**
	 * User expression for extracting the feed title from the source page.
	 * If this is left blank or does not provide any data {@see BridgeAbstract::getName()}
	 * is used instead as the feed's title.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionTitle()} to read this parameter
	 */
	const USER_EXPRESSION_FEED_TITLE = '';

	/**
	 * User expression for extracting the feed favicon URL from the source page.
	 * If this is left blank or does not provide any data {@see BridgeAbstract::getIcon()}
	 * is used instead as the feed's favicon URL.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionIcon()} to read this parameter
	 */
	const USER_EXPRESSION_FEED_ICON = '';

	/**
	 * User expression for extracting the feed items from the source page Enter
	 * an User expression matching a list of nodes, each node containing one
	 * feed article item in total This will be the context nodes for all of the
	 * following expressions.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItem()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM = '';

	/**
	 * User expression for extracting an item title from the item context
	 * This expression should match a node contained within each article item node
	 * containing the article headline.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItemTitle()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM_TITLE = '';

	/**
	 * User expression for extracting an item's content from the item context
	 * This expression should match a node contained within each article item node
	 * containing the article content or description.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItemContent()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM_CONTENT = '';

	/**
	 * User expression for extracting an item link from the item context
	 * This expression should match a node's attribute containing the article URL
	 * (usually the href attribute of an <a> tag). It should start with a dot
	 * followed by two forward slashes, referring to any descendant nodes of
	 * the article item node. Attributes can be selected by prepending an @ char
	 * before the attributes name.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItemUri()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM_URI = '';

	/**
	 * User expression for extracting an item author from the item context
	 * This expression should match a node contained within each article item
	 * node containing the article author's name. It should start with a dot
	 * followed by two forward slashes, referring to any descendant nodes of
	 * the article item node.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItemAuthor()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM_AUTHOR = '';

	/**
	 * User expression for extracting an item timestamp from the item context
	 * This expression should match a node or node's attribute containing the
	 * article timestamp or date (parsable by PHP's strtotime function). It
	 * should start with a dot followed by two forward slashes, referring to
	 * any descendant nodes of the article item node. Attributes can be
	 * selected by prepending an @ char before the attributes name.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItemTimestamp()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM_TIMESTAMP = '';

	/**
	 * User expression for extracting item enclosures (media content like
	 * images or movies) from the item context
	 * This expression should match a node's attribute containing an article
	 * image URL (usually the src attribute of an <img> tag or a style
	 * attribute). It should start with a dot followed by two forward slashes,
	 * referring to any descendant nodes of the article item node. Attributes
	 * can be selected by prepending an @ char before the attributes name.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItemEnclosures()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM_ENCLOSURES = '';

	/**
	 * User expression for extracting an item category from the item context
	 * This expression should match a node or node's attribute contained
	 * within each article item node containing the article category. This
	 * could be inside <div> or <span> tags or sometimes be hidden
	 * in a data attribute. It should start with a dot followed by two
	 * forward slashes, referring to any descendant nodes of the article
	 * item node. Attributes can be selected by prepending an @ char
	 * before the attributes name.
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItemCategories()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM_CATEGORIES = '';

	/**
	 * User expression for extracting an item uid from the item content
	 *
	 * Use {@see UserDefinedAbstract::getExpressionItemUid()} to read this parameter
	 */
	const USER_EXPRESSION_ITEM_UID = '';

	/**
	 * Fix encoding
	 * Set this to true for fixing feed encoding by invoking PHP's utf8_decode
	 * function on all extracted texts. Try this in case you see "broken" or
	 * "weird" characters in your feed where you'd normally expect umlauts
	 * or any other non-ascii characters.
	 *
	 * Use {@see UserDefinedAbstract::getSettingFixEncoding()} to read this parameter
	 */
	const SETTING_FIX_ENCODING = false;

	/**
	 * Internal storage for resulting feed name, automatically detected
	 * @var string
	 */
	private $feedName;

	/**
	 * Internal storage for resulting feed name, automatically detected
	 * @var string
	 */
	private $feedUri;

	/**
	 * Internal storage for resulting feed favicon, automatically detected
	 * @var string
	 */
	private $feedIcon;

	public function getName() {
		return $this->feedName ?: parent::getName();
	}

	public function getURI() {
		return $this->feedUri ?: parent::getURI();
	}

	public function getIcon() {
		return $this->feedIcon ?: parent::getIcon();
	}

	/**
	 * Source URL
	 * @return string
	 */
	protected function getSourceUrl(){
		return static::FEED_SOURCE_URL;
	}

	/**
	 * User expression for extracting the feed title from the source page
	 * @return string
	 */
	protected function getExpressionTitle(){
		return static::USER_EXPRESSION_FEED_TITLE;
	}

	/**
	 * User expression for extracting the feed favicon from the source page
	 * @return string
	 */
	protected function getExpressionIcon(){
		return static::USER_EXPRESSION_FEED_ICON;
	}

	/**
	 * User expression for extracting the feed items from the source page
	 * @return string
	 */
	protected function getExpressionItem(){
		return static::USER_EXPRESSION_ITEM;
	}

	/**
	 * User expression for extracting an item title from the item context
	 * @return string
	 */
	protected function getExpressionItemTitle(){
		return static::USER_EXPRESSION_ITEM_TITLE;
	}

	/**
	 * User expression for extracting an item's content from the item context
	 * @return string
	 */
	protected function getExpressionItemContent(){
		return static::USER_EXPRESSION_ITEM_CONTENT;
	}

	/**
	 * User expression for extracting an item link from the item context
	 * @return string
	 */
	protected function getExpressionItemUri(){
		return static::USER_EXPRESSION_ITEM_URI;
	}

	/**
	 * User expression for extracting an item author from the item context
	 * @return string
	 */
	protected function getExpressionItemAuthor(){
		return static::USER_EXPRESSION_ITEM_AUTHOR;
	}

	/**
	 * User expression for extracting an item timestamp from the item context
	 * @return string
	 */
	protected function getExpressionItemTimestamp(){
		return static::USER_EXPRESSION_ITEM_TIMESTAMP;
	}

	/**
	 * User expression for extracting item enclosures (media content like
	 * images or movies) from the item context
	 * @return string
	 */
	protected function getExpressionItemEnclosures(){
		return static::USER_EXPRESSION_ITEM_ENCLOSURES;
	}

	/**
	 * User expression for extracting an item category from the item context
	 * @return string
	 */
	protected function getExpressionItemCategories(){
		return static::USER_EXPRESSION_ITEM_CATEGORIES;
	}

	/**
	 * User expression for extracting an item uid from the item context
	 * @return string
	 */
	protected function getExpressionItemUid(){
		return static::USER_EXPRESSION_ITEM_UID;
	}

	/**
	 * Fix encoding
	 * @return string
	 */
	protected function getSettingFixEncoding(){
		return static::SETTING_FIX_ENCODING;
	}

	/**
	 * Internal helper method for quickly accessing all the user defined constants
	 * in derived classes
	 *
	 * @param $name
	 * @return bool|string
	 */
	private function getParam($name) {
		switch($name) {

			case 'url':
				return $this->getSourceUrl();
			case 'feed_title':
				return $this->getExpressionTitle();
			case 'feed_icon':
				return $this->getExpressionIcon();
			case 'item':
				return $this->getExpressionItem();
			case 'title':
				return $this->getExpressionItemTitle();
			case 'content':
				return $this->getExpressionItemContent();
			case 'uri':
				return $this->getExpressionItemUri();
			case 'author':
				return $this->getExpressionItemAuthor();
			case 'timestamp':
				return $this->getExpressionItemTimestamp();
			case 'enclosures':
				return $this->getExpressionItemEnclosures();
			case 'categories':
				return $this->getExpressionItemCategories();
			case 'uid':
				return $this->getExpressionItemUid();
			case 'fix_encoding':
				return $this->getSettingFixEncoding();
		}
	}

	/**
	 * Should provide the source website content.
	 *
	 */
	protected function provideWebsiteContent() {
		return getContents($this->feedUri);
	}

	/**
	 * Should transform website content into the internal data representation.
	 * Since there is a large degree of variability in how the content must be
	 * processed (class definition, decoding, etc), this function must be
	 * implemented when this class is inherited. It should call
	 * `provideWebsiteContent`
	 *
	 * It should return the data in the form that will be used by
	 * `convertUserQuery` For example, XPathAbstract::provideWebsiteContent
	 * returns DOMXPath.
	 *
	 */
	abstract protected function provideWebsiteData();

	/**
	 * Transforms input data based on a user query. The input is assumed to be
	 * the same form as the return value of `provideWebsiteContent`. The context
	 * is an optional input that is either null or data of the current feed item
	 *
	 * @param $data
	 * @param $query
	 * @param $context
	 */
	abstract protected function convertUserQuery($data, $query, $context);

	/**
	 * Checks if the value returned by `convertUserQuery` has any usable data in
	 * it. Returns true if there is no data and false otherwise.
	 *
	 * @param $data
	 * @return bool
	 */
	abstract protected function isEmpty($data);

	/**
	 * Transforms an object of the type from `convertUserQuery` and returns a
	 * string.
	 *
	 * @param $data
	 * @return string
	 */
	abstract protected function getDataValue($data);

	/**
	 * Should provide the feed's title
	 *
	 * @return string
	 */
	protected function provideFeedTitle($data) {
		$title = $this->convertUserQuery($data, $this->getParam('feed_title'), null);
		if(!$this->isEmpty($title)) {
			return $this->getDataValue($title);
		}
	}

	/**
	 * Should provide the URL of the feed's favicon
	 *
	 * @return string
	 */
	protected function provideFeedIcon($data) {
		$icon = $this->convertUserQuery($data, $this->getParam('feed_title'), null);
		if(!$this->isEmpty($icon)) {
			return $this->getDataValue($icon);
		}
	}

	/**
	 * Should provide the feed's items as an iterable.
	 *
	 * @param $data
	 */
	protected function provideFeedItems($data) {
		return $this->convertUserQuery($data, $this->getParam('item'), null);
	}

	public function collectData() {

		$this->feedUri = $this->getParam('url');

		$data = $this->provideWebsiteData();

		$this->feedName = $this->provideFeedTitle($data);
		$this->feedIcon = $this->provideFeedIcon($data);

		$entries = $this->provideFeedItems($data);
		if(count($entries) === 0) {
			return;
		}

		foreach ($entries as $entry) {
			$item = new \FeedItem();
			foreach(array('title', 'content', 'uri', 'author', 'timestamp', 'enclosures', 'categories', 'uid') as $param) {

				$expression = $this->getParam($param);
				if('' === $expression) {
					continue;
				}

				$result = $this->convertUserQuery($data, $expression, $entry);
				if ($this->isEmpty($result)) {
					continue;
				}

				$formatted = $this->formatParamValue($param, $this->getDataValue($result));
				if (!empty($formatted)) {
					$item->__set($param, $formatted);
				}
			}

			$this->items[] = $item;
		}

	}

	/**
	 * @param $param
	 * @param $value
	 * @return string|array
	 */
	protected function formatParamValue($param, $value)
	{
		$value = $this->fixEncoding($value);
		switch ($param) {
			case 'title':
				return $this->formatItemTitle($value);
			case 'content':
				return $this->formatItemContent($value);
			case 'uri':
				return $this->formatItemUri($value);
			case 'author':
				return $this->formatItemAuthor($value);
			case 'timestamp':
				return $this->formatItemTimestamp($value);
			case 'enclosures':
				return $this->formatItemEnclosures($value);
			case 'categories':
				return $this->formatItemCategories($value);
			case 'uid':
				return $this->formatItemUid($value);
		}
		return $value;
	}

	/**
	 * Formats the title of a feed item. Takes extracted raw title and returns it formatted
	 * as string.
	 * Can be easily overwritten for in case the value needs to be transformed into something
	 * else.
	 * @param string $value
	 * @return string
	 */
	protected function formatItemTitle($value) {
		return $value;
	}

	/**
	 * Formats the timestamp of a feed item. Takes extracted raw timestamp and returns unix
	 * timestamp as integer.
	 * Can be easily overwritten for example if a special format has to be expected on the
	 * source website.
	 * @param string $value
	 * @return string
	 */
	protected function formatItemContent($value) {
		return $value;
	}

	/**
	 * Formats the URI of a feed item. Takes extracted raw URI and returns it formatted
	 * as string.
	 * Can be easily overwritten for in case the value needs to be transformed into something
	 * else.
	 * @param string $value
	 * @return string
	 */
	protected function formatItemUri($value) {
		if(strlen($value) === 0) {
			return '';
		}
		if(strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
			return $value;
		}

		return urljoin($this->feedUri, $value);
	}

	/**
	 * Formats the author of a feed item. Takes extracted raw author and returns it formatted
	 * as string.
	 * Can be easily overwritten for in case the value needs to be transformed into something
	 * else.
	 * @param string $value
	 * @return string
	 */
	protected function formatItemAuthor($value) {
		return $value;
	}

	/**
	 * Formats the timestamp of a feed item. Takes extracted raw timestamp and returns unix
	 * timestamp as integer.
	 * Can be easily overwritten for example if a special format has to be expected on the
	 * source website.
	 * @param string $value
	 * @return false|int
	 */
	protected function formatItemTimestamp($value) {
		return strtotime($value);
	}

	/**
	 * Formats the enclosures of a feed item. Takes extracted raw enclosures and returns them
	 * formatted as array.
	 * Can be easily overwritten for in case the values need to be transformed into something
	 * else.
	 * @param $value
	 * @return array
	 */
	protected function formatItemEnclosures($value) {
		return $value;
	}

	/**
	 * Formats the categories of a feed item. Takes extracted raw categories and returns them
	 * formatted as array.
	 * Can be easily overwritten for in case the values need to be transformed into something
	 * else.
	 * @param $value
	 * @return array
	 */
	protected function formatItemCategories($value) {
		return $value;
	}

	/**
	 * Formats the uid of a feed item. returns null if the input is empty.
	 * Can be easily overwritten for in case the values need to be transformed into something
	 * else.
	 * @param string $value
	 * @return null|string
	 */
	protected function formatItemUid($value) {
		return empty($value) ? null : $value;
	}

	/**
	 * Fixes feed encoding by invoking PHP's utf8_decode function on extracted texts.
	 * Useful in case of "broken" or "weird" characters in the feed where you'd normally
	 * expect umlauts.
	 *
	 * @param $input
	 * @return string
	 */
	protected function fixEncoding($input) {
		return $this->getParam('fix_encoding') ? utf8_decode($input) : $input;
	}

	/**
	 * @param $mediaUrl
	 * @return string|void
	 */
	protected function cleanMediaUrl($mediaUrl) {
		$pattern = '~(?:http(?:s)?:)?[\/a-zA-Z0-9\-_\.\%]+\.(?:jpg|gif|png|jpeg|ico|mp3){1}~i';
		$result = preg_match($pattern, $mediaUrl, $matches);
		if(1 !== $result) {
			return;
		}
		return urljoin($this->feedUri, $matches[0]);
	}
}
