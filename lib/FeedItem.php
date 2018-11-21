<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	http://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

/**
 * Represents a simple feed item for transformation into various feed formats.
 *
 * This class represents a feed item. A feed item is an entity that can be
 * transformed into various feed formats. It holds a set of pre-defined
 * properties:
 *
 * - **URI**: URI to the full article (i.e. "https://...")
 * - **Title**: The title
 * - **Timestamp**: A timestamp of when the item was first released
 * - **Author**: Name of the author
 * - **Content**: Body of the feed, as text or HTML
 * - **Enclosures**: A list of links to media objects (images, videos, etc...)
 * - **Categories**: A list of category names or tags to categorize the item
 *
 * _Note_: A feed item can have any number of additional parameters, all of which
 * may or may not be transformed to the selected output format.
 *
 * _Remarks_: This class supports legacy items via {@see FeedItem::__construct()}
 * (i.e. `$feedItem = \FeedItem($item);`). Support for legacy items may be removed
 * in future versions of RSS-Bridge.
 */
class FeedItem {
	/** @var string|null URI to the full article */
	protected $uri = null;

	/** @var string|null Title of the item */
	protected $title = null;

	/** @var int|null Timestamp of when the item was first released */
	protected $timestamp = null;

	/** @var string|null Name of the author */
	protected $author = null;

	/** @var string|null Body of the feed */
	protected $content = null;

	/** @var array List of links to media objects */
	protected $enclosures = array();

	/** @var array List of category names or tags */
	protected $categories = array();

	/** @var array Associative list of additional parameters */
	protected $misc = array(); // Custom parameters

	/**
	 * Create object from legacy item.
	 *
	 * The provided array must be an associative array of key-value-pairs, where
	 * keys may correspond to any of the properties of this class.
	 *
	 * Example use:
	 *
	 * ```PHP
	 * <?php
	 * $item = array();
	 *
	 * $item['uri'] = 'https://www.github.com/rss-bridge/rss-bridge/';
	 * $item['title'] = 'Title';
	 * $item['timestamp'] = strtotime('now');
	 * $item['autor'] = 'Unknown author';
	 * $item['content'] = 'Hello World!';
	 * $item['enclosures'] = array('https://github.com/favicon.ico');
	 * $item['categories'] = array('php', 'rss-bridge', 'awesome');
	 *
	 * $feedItem = new \FeedItem($item);
	 *
	 * ```
	 *
	 * The result of the code above is the same as the code below:
	 *
	 * ```PHP
	 * <?php
	 * $feedItem = \FeedItem();
	 *
	 * $feedItem->uri = 'https://www.github.com/rss-bridge/rss-bridge/';
	 * $feedItem->title = 'Title';
	 * $feedItem->timestamp = strtotime('now');
	 * $feedItem->autor = 'Unknown author';
	 * $feedItem->content = 'Hello World!';
	 * $feedItem->enclosures = array('https://github.com/favicon.ico');
	 * $feedItem->categories = array('php', 'rss-bridge', 'awesome');
	 * ```
	 *
	 * @param array $item (optional) A legacy item (empty: no legacy support).
	 * @return object A new object of this class
	 */
	public function __construct($item = array()) {
		if(!is_array($item))
			throw new \InvalidArgumentException('Item must be an array!');

		foreach($item as $key => $value) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Get current URI.
	 *
	 * Use {@see FeedItem::setURI()} to set the URI.
	 *
	 * @return string|null The URI or null if it hasn't been set.
	 */
	public function getURI() {
		return $this->uri;
	}

	/**
	 * Set URI to the full article.
	 *
	 * Use {@see FeedItem::getURI()} to get the URI.
	 *
	 * _Note_: Removes whitespace from the beginning and end of the URI.
	 *
	 * @param string $uri URI to the full article.
	 * @return self
	 *
	 * @throws \InvalidArgumentException if the provided URI is not a string
	 * @throws \InvalidArgumentException if the provided URI is not a valid URI.
	 * A valid URI **must** include a scheme, host and path.
	 * @throws \InvalidArgumentException if the scheme of the provided URI is not
	 * "http" or "https".
	 */
	public function setURI($uri) {
		$this->uri = null; // Clear previous data

		if(!is_string($uri)) {
			throw new \InvalidArgumentException('URI must be a string!');
		}

		if(!filter_var(
			$uri,
			FILTER_VALIDATE_URL,
			FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_PATH_REQUIRED)) {
			throw new \InvalidArgumentException('URI must include a scheme, host and path!');
		}

		$scheme = parse_url($uri, PHP_URL_SCHEME);

		if($scheme !== 'http' && $scheme !== 'https') {
			throw new \InvalidArgumentException('URI scheme must be "http" or "https"!');
		}

		$this->uri = trim($uri);

		return $this;
	}

	/**
	 * Get current title.
	 *
	 * Use {@see FeedItem::setTitle()} to set the title.
	 *
	 * @return string|null The current title or null if it hasn't been set.
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set title.
	 *
	 * Use {@see FeedItem::getTitle()} to get the title.
	 *
	 * _Note_: Removes whitespace from beginning and end of the title.
	 *
	 * @param string $title The title
	 * @return self
	 *
	 * @throws \InvalidArgumentException if the provided title is not a string.
	 */
	public function setTitle($title) {
		$this->title = null; // Clear previous data

		if(!is_string($title)) {
			throw new \InvalidArgumentException('Title must be a string!');
		}

		$this->title = trim($title);

		return $this;
	}

	/**
	 * Get current timestamp.
	 *
	 * Use {@see FeedItem::setTimestamp()} to set the timestamp.
	 *
	 * @return int|null The current timestamp or null if it hasn't been set.
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * Set timestamp of first release.
	 *
	 * _Note_: The timestamp must represent the number of seconds since
	 * January 1 1970 00:00:00 GMT (Unix time).
	 *
	 * @link http://php.net/manual/en/function.strtotime.php strtotime (PHP)
	 * @link https://en.wikipedia.org/wiki/Unix_time Unix time (Wikipedia)
	 *
	 * @param int $timestamp A timestamp of when the item was first released
	 * @return self
	 *
	 * @throws \InvalidArgumentException if the provided timestamp is not an
	 * integer.
	 * @throws \LogicException if the provided timestamp is less than or equal
	 * to zero
	 */
	public function setTimestamp($timestamp) {
		$this->timestamp = null; // Clear previous data

		if(!is_int($timestamp)) {
			throw new \InvalidArgumentException('Timestamp must be an integer!');
		}

		if($timestamp <= 0) {
			throw new \LogicException('Timestamp must be greater than zero!');
		}

		$this->timestamp = $timestamp;

		return $this;
	}

	/**
	 * Get the current author name.
	 *
	 * Use {@see FeedItem::setAuthor()} to set the author.
	 *
	 * @return string|null The author or null if it hasn't been set.
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * Set the author name.
	 *
	 * Use {@see FeedItem::getAuthor()} to get the author.
	 *
	 * @param string $author The author name.
	 * @return self
	 *
	 * @throws \InvalidArgumentException if $author is not a string.
	 */
	public function setAuthor($author) {
		$this->author = null; // Clear previous data

		if(!is_string($author)) {
			throw new \InvalidArgumentException('Author must be a string!');
		}

		$this->author = $author;

		return $this;
	}

	/**
	 * Get item content.
	 *
	 * Use {@see FeedItem::setContent()} to set the item content.
	 *
	 * @return string|null The item content or null if it hasn't been set.
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Set item content.
	 *
	 * Note: This function casts objects of type simple_html_dom to string.
	 *
	 * Use {@see FeedItem::getContent()} to get the current item content.
	 *
	 * @param string|object $content The item content as text or simple_html_dom
	 * object.
	 * @return self
	 *
	 * @throws \InvalidArgumentException if $content is not a string
	 */
	public function setContent($content) {
		$this->content = null; // Clear previous data

		if($content instanceof simple_html_dom) {
			$content = (string)$content;
		}

		if(!is_string($content)) {
			throw new \InvalidArgumentException('Content must be a string!');
		}

		$this->content = $content;

		return $this;
	}

	/**
	 * Get item enclosures.
	 *
	 * Use {@see FeedItem::setEnclosures()} to set feed enclosures.
	 *
	 * @return array Enclosures as array of enclosure URIs.
	 */
	public function getEnclosures() {
		return $this->enclosures;
	}

	/**
	 * Set item enclosures.
	 *
	 * Use {@see FeedItem::getEnclosures()} to get the current item enclosures.
	 *
	 * @param array $enclosures Array of enclosures, where each element links to
	 * one enclosure.
	 * @return self
	 *
	 * @throws \InvalidArgumentException if $enclosures is not an array.
	 * @throws \InvalidArgumentException if any of the elements of $enclosures
	 * is not a valid URI. A valid URI **must** include a scheme, host and path.
	 */
	public function setEnclosures($enclosures) {
		$this->enclosures = array(); // Clear previous data

		if(!is_array($enclosures)) {
			throw new \InvalidArgumentException('Enclosures must be an array!');
		}

		foreach($enclosures as $enclosure) {

			if(!filter_var(
				$enclosure,
				FILTER_VALIDATE_URL,
				FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_PATH_REQUIRED)) {
				throw new \InvalidArgumentException('Each enclosure must contain a scheme, host and path!');
			}

		}

		$this->enclosures = $enclosures;

		return $this;
	}

	/**
	 * Get item categories.
	 *
	 * Use {@see FeedItem::setCategories()} to set item categories.
	 *
	 * @param array The item categories.
	 */
	public function getCategories() {
		return $this->categories;
	}

	/**
	 * Set item categories.
	 *
	 * Use {@see FeedItem::getCategories()} to get the current item categories.
	 *
	 * @param array $categories Array of categories, where each element defines
	 * a single category name.
	 * @return self
	 *
	 * @throws \InvalidArgumentException if $categories is not an array.
	 * @throws \InvalidArgumentException if any of the elements of $categories
	 * is not a string.
	 */
	public function setCategories($categories) {
		$this->categories = array(); // Clear previous data

		if(!is_array($categories)) {
			throw new \InvalidArgumentException('Categories must be an array!');
		}

		foreach($categories as $category) {

			if(!is_string($category)) {
				throw new \InvalidArgumentException('Category must be a string!');
			}

		}

		$this->categories = $categories;

		return $this;
	}

	/**
	 * Add miscellaneous elements to the item.
	 *
	 * @param string $key Name of the element.
	 * @param mixed $value Value of the element.
	 * @return self
	 *
	 * @throws \InvalidArgumentException if $key is not a string.
	 * @throws \LogicException if $key is any of the standard parameters (uri,
	 * title, timestamp, author, etc...)
	 */
	public function addMisc($key, $value) {

		if(!is_string($key)) {
			throw new \InvalidArgumentException('Key must be a string!');
		}

		if(in_array($key, get_object_vars($this))) {
			throw new \LogicException('Key must be unique!');
		}

		$this->misc[$key] = $value;

		return $this;
	}

	/**
	 * Transform current object to array
	 *
	 * @return array
	 */
	public function toArray() {
		return array_merge(
			array(
				'uri' => $this->uri,
				'title' => $this->title,
				'timestamp' => $this->timestamp,
				'author' => $this->author,
				'content' => $this->content,
				'enclosures' => $this->enclosures,
				'categories' => $this->categories,
			), $this->misc
		);
	}

	/**
	 * Set item property
	 *
	 * Allows simple assignment to parameters. This method is slower, but easier
	 * to implement in some cases:
	 *
	 * ```PHP
	 * $item = new \FeedItem();
	 * $item->content = 'Hello World!';
	 * $item->my_id = 42;
	 * ```
	 *
	 * @param string $name Property name
	 * @param mixed $value Property value
	 */
	function __set($name, $value) {
		switch($name) {
			case 'uri': $this->setURI($value); break;
			case 'title': $this->setTitle($value); break;
			case 'timestamp': $this->setTimestamp($value); break;
			case 'author': $this->setAuthor($value); break;
			case 'content': $this->setContent($value); break;
			case 'enclosures': $this->setEnclosures($value); break;
			case 'categories': $this->setCategories($value); break;
			default: $this->addMisc($name, $value);
		}
	}

	/**
	 * Get item property
	 *
	 * Allows simple assignment to parameters. This method is slower, but easier
	 * to implement in some cases.
	 *
	 * @param string $name Property name
	 * @return mixed Property value
	 */
	function __get($name) {
		switch($name) {
			case 'uri': return $this->getURI();
			case 'title': return $this->getTitle();
			case 'timestamp': return $this->getTimestamp();
			case 'author': return $this->getAuthor();
			case 'content': return $this->getContent();
			case 'enclosures': return $this->getEnclosures();
			case 'categories': return $this->getCategories();
			default:
				if(array_key_exists($name, $this->misc))
					return $this->misc[$name];
				return null;
		}
	}
}
