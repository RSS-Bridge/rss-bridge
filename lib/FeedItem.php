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

	/** @var string Unique ID for the current item */
	protected $uid = null;

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
			Debug::log('Item must be an array!');

		foreach($item as $key => $value) {
			$this->__set($key, $value);
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
	 * _Remarks_: Uses the attribute "href" or "src" if the provided URI is an
	 * object of simple_html_dom_node.
	 *
	 * @param object|string $uri URI to the full article.
	 * @return self
	 */
	public function setURI($uri) {
		$this->uri = null; // Clear previous data

		if($uri instanceof simple_html_dom_node) {
			if($uri->hasAttribute('href')) { // Anchor
				$uri = $uri->href;
			} elseif($uri->hasAttribute('src')) { // Image
				$uri = $uri->src;
			} else {
				Debug::log('The item provided as URI is unknown!');
			}
		}

		if(!is_string($uri)) {
			Debug::log('URI must be a string!');
		} elseif(!filter_var(
			$uri,
			FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
			Debug::log('URI must include a scheme, host and path!');
		} else {
			$scheme = parse_url($uri, PHP_URL_SCHEME);

			if($scheme !== 'http' && $scheme !== 'https') {
				Debug::log('URI scheme must be "http" or "https"!');
			} else {
				$this->uri = trim($uri);
			}
		}

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
	 */
	public function setTitle($title) {
		$this->title = null; // Clear previous data

		if(!is_string($title)) {
			Debug::log('Title must be a string!');
		} else {
			$this->title = trim($title);
		}

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
	 * _Note_: The timestamp should represent the number of seconds since
	 * January 1 1970 00:00:00 GMT (Unix time).
	 *
	 * _Remarks_: If the provided timestamp is a string (not numeric), this
	 * function automatically attempts to parse the string using
	 * [strtotime](http://php.net/manual/en/function.strtotime.php)
	 *
	 * @link http://php.net/manual/en/function.strtotime.php strtotime (PHP)
	 * @link https://en.wikipedia.org/wiki/Unix_time Unix time (Wikipedia)
	 *
	 * @param string|int $timestamp A timestamp of when the item was first released
	 * @return self
	 */
	public function setTimestamp($timestamp) {
		$this->timestamp = null; // Clear previous data

		if(!is_numeric($timestamp)
		&& !$timestamp = strtotime($timestamp)) {
			Debug::log('Unable to parse timestamp!');
		}

		if($timestamp <= 0) {
			Debug::log('Timestamp must be greater than zero!');
		} else {
			$this->timestamp = $timestamp;
		}

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
	 */
	public function setAuthor($author) {
		$this->author = null; // Clear previous data

		if(!is_string($author)) {
			Debug::log('Author must be a string!');
		} else {
			$this->author = $author;
		}

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
	 * Note: This function casts objects of type simple_html_dom and
	 * simple_html_dom_node to string.
	 *
	 * Use {@see FeedItem::getContent()} to get the current item content.
	 *
	 * @param string|object $content The item content as text or simple_html_dom
	 * object.
	 * @return self
	 */
	public function setContent($content) {
		$this->content = null; // Clear previous data

		if($content instanceof simple_html_dom
		|| $content instanceof simple_html_dom_node) {
			$content = (string)$content;
		}

		if(!is_string($content)) {
			Debug::log('Content must be a string!');
		} else {
			$this->content = $content;
		}

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
	 */
	public function setEnclosures($enclosures) {
		$this->enclosures = array(); // Clear previous data

		if(!is_array($enclosures)) {
			Debug::log('Enclosures must be an array!');
		} else {
			foreach($enclosures as $enclosure) {
				if(!filter_var(
					$enclosure,
					FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
					Debug::log('Each enclosure must contain a scheme, host and path!');
				} else {
					$this->enclosures[] = $enclosure;
				}
			}
		}

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
	 */
	public function setCategories($categories) {
		$this->categories = array(); // Clear previous data

		if(!is_array($categories)) {
			Debug::log('Categories must be an array!');
		} else {
			foreach($categories as $category) {
				if(!is_string($category)) {
					Debug::log('Category must be a string!');
				} else {
					$this->categories[] = $category;
				}
			}
		}

		return $this;
	}

	/**
	 * Get unique id
	 *
	 * Use {@see FeedItem::setUid()} to set the unique id.
	 *
	 * @param string The unique id.
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Set unique id.
	 *
	 * Use {@see FeedItem::getUid()} to get the unique id.
	 *
	 * @param string $uid A string that uniquely identifies the current item
	 * @return self
	 */
	public function setUid($uid) {
		$this->uid = null; // Clear previous data

		if(!is_string($uid)) {
			Debug::log('Unique id must be a string!');
		} else {
			$this->uid = sha1($uid);
		}

		return $this;
	}

	/**
	 * Add miscellaneous elements to the item.
	 *
	 * @param string $key Name of the element.
	 * @param mixed $value Value of the element.
	 * @return self
	 */
	public function addMisc($key, $value) {

		if(!is_string($key)) {
			Debug::log('Key must be a string!');
		} elseif(in_array($key, get_object_vars($this))) {
			Debug::log('Key must be unique!');
		} else {
			$this->misc[$key] = $value;
		}

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
				'uid' => $this->uid,
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
			case 'uid': $this->setUid($value); break;
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
			case 'uid': return $this->getUid();
			default:
				if(array_key_exists($name, $this->misc))
					return $this->misc[$name];
				return null;
		}
	}
}
