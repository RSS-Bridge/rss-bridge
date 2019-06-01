<?php
/**
 * Website: http://sourceforge.net/projects/simplehtmldom/
 * Additional projects: http://sourceforge.net/projects/debugobject/
 * Acknowledge: Jose Solorzano (https://sourceforge.net/projects/php-html/)
 * Contributions by:
 *	 Yousuke Kumakura (Attribute filters)
 *	 Vadim Voituk (Negative indexes supports of "find" method)
 *	 Antcs (Constructor with automatically load contents either text or file/url)
 *
 * all affected sections have comments starting with "PaperG"
 *
 * Paperg - Added case insensitive testing of the value of the selector.
 *
 * Paperg - Added tag_start for the starting index of tags - NOTE: This works
 * but not accurately. This tag_start gets counted AFTER \r\n have been crushed
 * out, and after the remove_noice calls so it will not reflect the REAL
 * position of the tag in the source, it will almost always be smaller by some
 * amount. We use this to determine how far into the file the tag in question
 * is. This "percentage" will never be accurate as the $dom->size is the "real"
 * number of bytes the dom was created from. But for most purposes, it's a
 * really good estimation.
 *
 * Paperg - Added the forceTagsClosed to the dom constructor. Forcing tags
 * closed is great for malformed html, but it CAN lead to parsing errors.
 *
 * Allow the user to tell us how much they trust the html.
 *
 * Paperg add the text and plaintext to the selectors for the find syntax.
 * plaintext implies text in the innertext of a node.  text implies that the
 * tag is a text node. This allows for us to find tags based on the text they
 * contain.
 *
 * Create find_ancestor_tag to see if a tag is - at any level - inside of
 * another specific tag.
 *
 * Paperg: added parse_charset so that we know about the character set of
 * the source document. NOTE: If the user's system has a routine called
 * get_last_retrieve_url_contents_content_type availalbe, we will assume it's
 * returning the content-type header from the last transfer or curl_exec, and
 * we will parse that and use it in preference to any other method of charset
 * detection.
 *
 * Found infinite loop in the case of broken html in restore_noise. Rewrote to
 * protect from that.
 *
 * PaperG (John Schlick) Added get_display_size for "IMG" tags.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author S.C. Chen <me578022@gmail.com>
 * @author John Schlick
 * @author Rus Carroll
 * @version Rev. 1.8.1 (247)
 * @package PlaceLocalInclude
 * @subpackage simple_html_dom
 */

/**
 * All of the Defines for the classes below.
 * @author S.C. Chen <me578022@gmail.com>
 */
define('HDOM_TYPE_ELEMENT', 1);
define('HDOM_TYPE_COMMENT', 2);
define('HDOM_TYPE_TEXT', 3);
define('HDOM_TYPE_ENDTAG', 4);
define('HDOM_TYPE_ROOT', 5);
define('HDOM_TYPE_UNKNOWN', 6);
define('HDOM_QUOTE_DOUBLE', 0);
define('HDOM_QUOTE_SINGLE', 1);
define('HDOM_QUOTE_NO', 3);
define('HDOM_INFO_BEGIN', 0);
define('HDOM_INFO_END', 1);
define('HDOM_INFO_QUOTE', 2);
define('HDOM_INFO_SPACE', 3);
define('HDOM_INFO_TEXT', 4);
define('HDOM_INFO_INNER', 5);
define('HDOM_INFO_OUTER', 6);
define('HDOM_INFO_ENDSPACE', 7);

/** The default target charset */
defined('DEFAULT_TARGET_CHARSET') || define('DEFAULT_TARGET_CHARSET', 'UTF-8');

/** The default <br> text used instead of <br> tags when returning text */
defined('DEFAULT_BR_TEXT') || define('DEFAULT_BR_TEXT', "\r\n");

/** The default <span> text used instead of <span> tags when returning text */
defined('DEFAULT_SPAN_TEXT') || define('DEFAULT_SPAN_TEXT', ' ');

/** The maximum file size the parser should load */
defined('MAX_FILE_SIZE') || define('MAX_FILE_SIZE', 600000);

/** Contents between curly braces "{" and "}" are interpreted as text */
define('HDOM_SMARTY_AS_TEXT', 1);

// helper functions
// -----------------------------------------------------------------------------
// get html dom from file
// $maxlen is defined in the code as PHP_STREAM_COPY_ALL which is defined as -1.
function file_get_html(
	$url,
	$use_include_path = false,
	$context = null,
	$offset = 0,
	$maxLen = -1,
	$lowercase = true,
	$forceTagsClosed = true,
	$target_charset = DEFAULT_TARGET_CHARSET,
	$stripRN = true,
	$defaultBRText = DEFAULT_BR_TEXT,
	$defaultSpanText = DEFAULT_SPAN_TEXT)
{
	// Ensure maximum length is greater than zero
	if($maxLen <= 0) { $maxLen = MAX_FILE_SIZE; }

	// We DO force the tags to be terminated.
	$dom = new simple_html_dom(
		null,
		$lowercase,
		$forceTagsClosed,
		$target_charset,
		$stripRN,
		$defaultBRText,
		$defaultSpanText);

	/**
	 * For sourceforge users: uncomment the next line and comment the
	 * retrieve_url_contents line 2 lines down if it is not already done.
	 */
	$contents = file_get_contents(
		$url,
		$use_include_path,
		$context,
		$offset,
		$maxLen);

	// Paperg - use our own mechanism for getting the contents as we want to
	// control the timeout.
	// $contents = retrieve_url_contents($url);
	if (empty($contents) || strlen($contents) > $maxLen) { return false; }

	// The second parameter can force the selectors to all be lowercase.
	$dom->load($contents, $lowercase, $stripRN);
	return $dom;
}

// get html dom from string
function str_get_html(
	$str,
	$lowercase = true,
	$forceTagsClosed = true,
	$target_charset = DEFAULT_TARGET_CHARSET,
	$stripRN = true,
	$defaultBRText = DEFAULT_BR_TEXT,
	$defaultSpanText = DEFAULT_SPAN_TEXT)
{
	$dom = new simple_html_dom(
		null,
		$lowercase,
		$forceTagsClosed,
		$target_charset,
		$stripRN,
		$defaultBRText,
		$defaultSpanText);

	if (empty($str) || strlen($str) > MAX_FILE_SIZE) {
		$dom->clear();
		return false;
	}

	$dom->load($str, $lowercase, $stripRN);
	return $dom;
}

// dump html dom tree
function dump_html_tree($node, $show_attr = true, $deep = 0)
{
	$node->dump($node);
}

/**
 * simple html dom node
 * PaperG - added ability for "find" routine to lowercase the value of the
 * selector.
 *
 * PaperG - added $tag_start to track the start position of the tag in the total
 * byte index
 *
 * @package PlaceLocalInclude
 */
class simple_html_dom_node
{
	/**
	 * Node type
	 *
	 * Default is {@see HDOM_TYPE_TEXT}
	 *
	 * @var int
	 */
	public $nodetype = HDOM_TYPE_TEXT;

	/**
	 * Tag name
	 *
	 * Default is 'text'
	 *
	 * @var string
	 */
	public $tag = 'text';

	/**
	 * List of attributes
	 *
	 * @var array
	 */
	public $attr = array();

	/**
	 * List of child node objects
	 *
	 * @var array
	 */
	public $children = array();
	public $nodes = array();

	/**
	 * The parent node object
	 *
	 * @var object|null
	 */
	public $parent = null;

	// The "info" array - see HDOM_INFO_... for what each element contains.
	public $_ = array();

	/**
	 * Start position of the tag in the document
	 *
	 * @var int
	 */
	public $tag_start = 0;

	/**
	 * The DOM object
	 *
	 * @var object|null
	 */
	private $dom = null;

	/**
	 * Construct new node object
	 *
	 * Adds itself to the list of DOM Nodes {@see simple_html_dom::$nodes}
	 */
	function __construct($dom)
	{
		$this->dom = $dom;
		$dom->nodes[] = $this;
	}

	function __destruct()
	{
		$this->clear();
	}

	function __toString()
	{
		return $this->outertext();
	}

	// clean up memory due to php5 circular references memory leak...
	function clear()
	{
		$this->dom = null;
		$this->nodes = null;
		$this->parent = null;
		$this->children = null;
	}

	// dump node's tree
	function dump($show_attr = true, $deep = 0)
	{
		$lead = str_repeat('	', $deep);

		echo $lead . $this->tag;

		if ($show_attr && count($this->attr) > 0) {
			echo '(';
			foreach ($this->attr as $k => $v) {
				echo "[$k]=>\"" . $this->$k . '", ';
			}
			echo ')';
		}

		echo "\n";

		if ($this->nodes) {
			foreach ($this->nodes as $c) {
				$c->dump($show_attr, $deep + 1);
			}
		}
	}


	// Debugging function to dump a single dom node with a bunch of information about it.
	function dump_node($echo = true)
	{
		$string = $this->tag;

		if (count($this->attr) > 0) {
			$string .= '(';
			foreach ($this->attr as $k => $v) {
				$string .= "[$k]=>\"" . $this->$k . '", ';
			}
			$string .= ')';
		}

		if (count($this->_) > 0) {
			$string .= ' $_ (';
			foreach ($this->_ as $k => $v) {
				if (is_array($v)) {
					$string .= "[$k]=>(";
					foreach ($v as $k2 => $v2) {
						$string .= "[$k2]=>\"" . $v2 . '", ';
					}
					$string .= ')';
				} else {
					$string .= "[$k]=>\"" . $v . '", ';
				}
			}
			$string .= ')';
		}

		if (isset($this->text)) {
			$string .= ' text: (' . $this->text . ')';
		}

		$string .= " HDOM_INNER_INFO: '";

		if (isset($node->_[HDOM_INFO_INNER])) {
			$string .= $node->_[HDOM_INFO_INNER] . "'";
		} else {
			$string .= ' NULL ';
		}

		$string .= ' children: ' . count($this->children);
		$string .= ' nodes: ' . count($this->nodes);
		$string .= ' tag_start: ' . $this->tag_start;
		$string .= "\n";

		if ($echo) {
			echo $string;
			return;
		} else {
			return $string;
		}
	}

	/**
	 * Return or set parent node
	 *
	 * @param object|null $parent (optional) The parent node, `null` to return
	 * the current parent node.
	 * @return object|null The parent node
	 */
	function parent($parent = null)
	{
		// I am SURE that this doesn't work properly.
		// It fails to unset the current node from it's current parents nodes or
		// children list first.
		if ($parent !== null) {
			$this->parent = $parent;
			$this->parent->nodes[] = $this;
			$this->parent->children[] = $this;
		}

		return $this->parent;
	}

	/**
	 * @return bool True if the node has at least one child node
	 */
	function has_child()
	{
		return !empty($this->children);
	}

	/**
	 * Get child node at specified index
	 *
	 * @param int $idx The index of the child node to return, `-1` to return all
	 * child nodes.
	 * @return object|array|null The child node at the specified index, all child
	 * nodes or null if the index is invalid.
	 */
	function children($idx = -1)
	{
		if ($idx === -1) {
			return $this->children;
		}

		if (isset($this->children[$idx])) {
			return $this->children[$idx];
		}

		return null;
	}

	/**
	 * Get first child node
	 *
	 * @return object|null The first child node or null if the current node has
	 * no child nodes.
	 *
	 * @todo Use `empty()` instead of `count()` to improve performance on large
	 * arrays.
	 */
	function first_child()
	{
		if (count($this->children) > 0) {
			return $this->children[0];
		}
		return null;
	}

	/**
	 * Get last child node
	 *
	 * @return object|null The last child node or null if the current node has
	 * no child nodes.
	 *
	 * @todo Use `end()` to slightly improve performance on large arrays.
	 */
	function last_child()
	{
		if (($count = count($this->children)) > 0) {
			return $this->children[$count - 1];
		}
		return null;
	}

	/**
	 * Get next sibling node
	 *
	 * @return object|null The sibling node or null if the current node has no
	 * sibling nodes.
	 */
	function next_sibling()
	{
		if ($this->parent === null) {
			return null;
		}

		$idx = 0;
		$count = count($this->parent->children);

		while ($idx < $count && $this !== $this->parent->children[$idx]) {
			++$idx;
		}

		if (++$idx >= $count) {
			return null;
		}

		return $this->parent->children[$idx];
	}

	/**
	 * Get previous sibling node
	 *
	 * @return object|null The sibling node or null if the current node has no
	 * sibling nodes.
	 */
	function prev_sibling()
	{
		if ($this->parent === null) { return null; }

		$idx = 0;
		$count = count($this->parent->children);

		while ($idx < $count && $this !== $this->parent->children[$idx]) {
			++$idx;
		}

		if (--$idx < 0) { return null; }

		return $this->parent->children[$idx];
	}

	/**
	 * Traverse ancestors to the first matching tag.
	 *
	 * @param string $tag Tag to find
	 * @return object|null First matching node in the DOM tree or null if no
	 * match was found.
	 *
	 * @todo Null is returned implicitly by calling ->parent on the root node.
	 * This behaviour could change at any time, rendering this function invalid.
	 */
	function find_ancestor_tag($tag)
	{
		global $debug_object;
		if (is_object($debug_object)) { $debug_object->debug_log_entry(1); }

		// Start by including ourselves in the comparison.
		$returnDom = $this;

		while (!is_null($returnDom)) {
			if (is_object($debug_object)) {
				$debug_object->debug_log(2, 'Current tag is: ' . $returnDom->tag);
			}

			if ($returnDom->tag == $tag) {
				break;
			}

			$returnDom = $returnDom->parent;
		}

		return $returnDom;
	}

	/**
	 * Get node's inner text (everything inside the opening and closing tags)
	 *
	 * @return string
	 */
	function innertext()
	{
		if (isset($this->_[HDOM_INFO_INNER])) {
			return $this->_[HDOM_INFO_INNER];
		}

		if (isset($this->_[HDOM_INFO_TEXT])) {
			return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
		}

		$ret = '';

		foreach ($this->nodes as $n) {
			$ret .= $n->outertext();
		}

		return $ret;
	}

	/**
	 * Get node's outer text (everything including the opening and closing tags)
	 *
	 * @return string
	 */
	function outertext()
	{
		global $debug_object;

		if (is_object($debug_object)) {
			$text = '';

			if ($this->tag === 'text') {
				if (!empty($this->text)) {
					$text = ' with text: ' . $this->text;
				}
			}

			$debug_object->debug_log(1, 'Innertext of tag: ' . $this->tag . $text);
		}

		if ($this->tag === 'root') return $this->innertext();

		// trigger callback
		if ($this->dom && $this->dom->callback !== null) {
			call_user_func_array($this->dom->callback, array($this));
		}

		if (isset($this->_[HDOM_INFO_OUTER])) {
			return $this->_[HDOM_INFO_OUTER];
		}

		if (isset($this->_[HDOM_INFO_TEXT])) {
			return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
		}

		// render begin tag
		if ($this->dom && $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]) {
			$ret = $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]->makeup();
		} else {
			$ret = '';
		}

		// render inner text
		if (isset($this->_[HDOM_INFO_INNER])) {
			// If it's a br tag...  don't return the HDOM_INNER_INFO that we
			// may or may not have added.
			if ($this->tag !== 'br') {
				$ret .= $this->_[HDOM_INFO_INNER];
			}
		} else {
			if ($this->nodes) {
				foreach ($this->nodes as $n) {
					$ret .= $this->convert_text($n->outertext());
				}
			}
		}

		// render end tag
		if (isset($this->_[HDOM_INFO_END]) && $this->_[HDOM_INFO_END] != 0) {
			$ret .= '</' . $this->tag . '>';
		}

		return $ret;
	}

	/**
	 * Get node's plain text (everything excluding all tags)
	 *
	 * @return string
	 */
	function text()
	{
		if (isset($this->_[HDOM_INFO_INNER])) {
			return $this->_[HDOM_INFO_INNER];
		}

		switch ($this->nodetype) {
			case HDOM_TYPE_TEXT: return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
			case HDOM_TYPE_COMMENT: return '';
			case HDOM_TYPE_UNKNOWN: return '';
		}

		if (strcasecmp($this->tag, 'script') === 0) { return ''; }
		if (strcasecmp($this->tag, 'style') === 0) { return ''; }

		$ret = '';

		// In rare cases, (always node type 1 or HDOM_TYPE_ELEMENT - observed
		// for some span tags, and some p tags) $this->nodes is set to NULL.
		// NOTE: This indicates that there is a problem where it's set to NULL
		// without a clear happening.
		// WHY is this happening?
		if (!is_null($this->nodes)) {
			foreach ($this->nodes as $n) {
				// Start paragraph after a blank line
				if ($n->tag === 'p') {
					$ret .= "\n\n";
				}

				$ret .= $this->convert_text($n->text());

				// If this node is a span... add a space at the end of it so
				// multiple spans don't run into each other.  This is plaintext
				// after all.
				if ($n->tag === 'span') {
					$ret .= $this->dom->default_span_text;
				}
			}
		}
		return trim($ret);
	}

	/**
	 * Get node's xml text (inner text as a CDATA section)
	 *
	 * @return string
	 */
	function xmltext()
	{
		$ret = $this->innertext();
		$ret = str_ireplace('<![CDATA[', '', $ret);
		$ret = str_replace(']]>', '', $ret);
		return $ret;
	}

	// build node's text with tag
	function makeup()
	{
		// text, comment, unknown
		if (isset($this->_[HDOM_INFO_TEXT])) {
			return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
		}

		$ret = '<' . $this->tag;
		$i = -1;

		foreach ($this->attr as $key => $val) {
			++$i;

			// skip removed attribute
			if ($val === null || $val === false) { continue; }

			$ret .= $this->_[HDOM_INFO_SPACE][$i][0];

			//no value attr: nowrap, checked selected...
			if ($val === true) {
				$ret .= $key;
			} else {
				switch ($this->_[HDOM_INFO_QUOTE][$i])
				{
					case HDOM_QUOTE_DOUBLE: $quote = '"'; break;
					case HDOM_QUOTE_SINGLE: $quote = '\''; break;
					default: $quote = '';
				}

				$ret .= $key
				. $this->_[HDOM_INFO_SPACE][$i][1]
				. '='
				. $this->_[HDOM_INFO_SPACE][$i][2]
				. $quote
				. $val
				. $quote;
			}
		}

		$ret = $this->dom->restore_noise($ret);
		return $ret . $this->_[HDOM_INFO_ENDSPACE] . '>';
	}

	/**
	 * Find elements by CSS selector
	 *
	 * @param string $selector The CSS selector
	 * @param int|null $idx Index of element to return form the list of matching
	 * elements (default: `null` = disabled).
	 * @param bool $lowercase Matches tag names case insensitive (lowercase) if
	 * enabled (default: `false`)
	 * @return array|object|null A list of elements matching the specified CSS
	 * selector or a single element if $idx is specified or null if no element
	 * was found.
	 */
	function find($selector, $idx = null, $lowercase = false)
	{
		$selectors = $this->parse_selector($selector);
		if (($count = count($selectors)) === 0) { return array(); }
		$found_keys = array();

		// find each selector
		for ($c = 0; $c < $count; ++$c) {
			// The change on the below line was documented on the sourceforge
			// code tracker id 2788009
			// used to be: if (($levle=count($selectors[0]))===0) return array();
			if (($levle = count($selectors[$c])) === 0) { return array(); }
			if (!isset($this->_[HDOM_INFO_BEGIN])) { return array(); }

			$head = array($this->_[HDOM_INFO_BEGIN] => 1);
			$cmd = ' '; // Combinator

			// handle descendant selectors, no recursive!
			for ($l = 0; $l < $levle; ++$l) {
				$ret = array();

				foreach ($head as $k => $v) {
					$n = ($k === -1) ? $this->dom->root : $this->dom->nodes[$k];
					//PaperG - Pass this optional parameter on to the seek function.
					$n->seek($selectors[$c][$l], $ret, $cmd, $lowercase);
				}

				$head = $ret;
				$cmd = $selectors[$c][$l][4]; // Next Combinator
			}

			foreach ($head as $k => $v) {
				if (!isset($found_keys[$k])) {
					$found_keys[$k] = 1;
				}
			}
		}

		// sort keys
		ksort($found_keys);

		$found = array();
		foreach ($found_keys as $k => $v) {
			$found[] = $this->dom->nodes[$k];
		}

		// return nth-element or array
		if (is_null($idx)) { return $found; }
		elseif ($idx < 0) { $idx = count($found) + $idx; }
		return (isset($found[$idx])) ? $found[$idx] : null;
	}

	/**
	 * Seek DOM elements by selector
	 *
	 * **Note**
	 * The selector element must be compatible to a selector from
	 * {@see simple_html_dom_node::parse_selector()}
	 *
	 * @param array $selector A selector element
	 * @param array $ret An array of matches
	 * @param bool $lowercase Matches tag names case insensitive (lowercase) if
	 * enabled (default: `false`)
	 * @return void
	 */
	protected function seek($selector, &$ret, $parent_cmd, $lowercase = false)
	{
		global $debug_object;
		if (is_object($debug_object)) { $debug_object->debug_log_entry(1); }

		list($tag, $id, $class, $attributes, $cmb) = $selector;
		$nodes = array();

		if ($parent_cmd === ' ') { // Descendant Combinator
			// Find parent closing tag if the current element doesn't have a closing
			// tag (i.e. void element)
			$end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
			if ($end == 0) {
				$parent = $this->parent;
				while (!isset($parent->_[HDOM_INFO_END]) && $parent !== null) {
					$end -= 1;
					$parent = $parent->parent;
				}
				$end += $parent->_[HDOM_INFO_END];
			}

			// Get list of target nodes
			$nodes_start = $this->_[HDOM_INFO_BEGIN] + 1;
			$nodes_count = $end - $nodes_start;
			$nodes = array_slice($this->dom->nodes, $nodes_start, $nodes_count, true);
		} elseif ($parent_cmd === '>') { // Child Combinator
			$nodes = $this->children;
		} elseif ($parent_cmd === '+'
			&& $this->parent
			&& in_array($this, $this->parent->children)) { // Next-Sibling Combinator
				$index = array_search($this, $this->parent->children, true) + 1;
				$nodes[] = $this->parent->children[$index];
		} elseif ($parent_cmd === '~'
			&& $this->parent
			&& in_array($this, $this->parent->children)) { // Subsequent Sibling Combinator
				$index = array_search($this, $this->parent->children, true);
				$nodes = array_slice($this->parent->children, $index);
		}

		// Go throgh each element starting at this element until the end tag
		// Note: If this element is a void tag, any previous void element is
		// skipped.
		foreach($nodes as $node) {
			$pass = true;

			// Skip root nodes
			if(!$node->parent) {
				$pass = false;
			}

			// Skip if node isn't a child node (i.e. text nodes)
			if($pass && !in_array($node, $node->parent->children, true)) {
				$pass = false;
			}

			// Skip if tag doesn't match
			if ($pass && $tag !== '' && $tag !== $node->tag && $tag !== '*') {
				$pass = false;
			}

			// Skip if ID doesn't exist
			if ($pass && $id !== '' && !isset($node->attr['id'])) {
				$pass = false;
			}

			// Check if ID matches
			if ($pass && $id !== '' && isset($node->attr['id'])) {
				// Note: Only consider the first ID (as browsers do)
				$node_id = explode(' ', trim($node->attr['id']))[0];

				if($id !== $node_id) { $pass = false; }
			}

			// Check if all class(es) exist
			if ($pass && $class !== '' && is_array($class) && !empty($class)) {
				if (isset($node->attr['class'])) {
					$node_classes = explode(' ', $node->attr['class']);

					if ($lowercase) {
						$node_classes = array_map('strtolower', $node_classes);
					}

					foreach($class as $c) {
						if(!in_array($c, $node_classes)) {
							$pass = false;
							break;
						}
					}
				} else {
					$pass = false;
				}
			}

			// Check attributes
			if ($pass
				&& $attributes !== ''
				&& is_array($attributes)
				&& !empty($attributes)) {
					foreach($attributes as $a) {
						list (
							$att_name,
							$att_expr,
							$att_val,
							$att_inv,
							$att_case_sensitivity
						) = $a;

						// Handle indexing attributes (i.e. "[2]")
						/**
						 * Note: This is not supported by the CSS Standard but adds
						 * the ability to select items compatible to XPath (i.e.
						 * the 3rd element within it's parent).
						 *
						 * Note: This doesn't conflict with the CSS Standard which
						 * doesn't work on numeric attributes anyway.
						 */
						if (is_numeric($att_name)
							&& $att_expr === ''
							&& $att_val === '') {
								$count = 0;

								// Find index of current element in parent
								foreach ($node->parent->children as $c) {
									if ($c->tag === $node->tag) ++$count;
									if ($c === $node) break;
								}

								// If this is the correct node, continue with next
								// attribute
								if ($count === (int)$att_name) continue;
						}

						// Check attribute availability
						if ($att_inv) { // Attribute should NOT be set
							if (isset($node->attr[$att_name])) {
								$pass = false;
								break;
							}
						} else { // Attribute should be set
							// todo: "plaintext" is not a valid CSS selector!
							if ($att_name !== 'plaintext'
								&& !isset($node->attr[$att_name])) {
									$pass = false;
									break;
							}
						}

						// Continue with next attribute if expression isn't defined
						if ($att_expr === '') continue;

						// If they have told us that this is a "plaintext"
						// search then we want the plaintext of the node - right?
						// todo "plaintext" is not a valid CSS selector!
						if ($att_name === 'plaintext') {
							$nodeKeyValue = $node->text();
						} else {
							$nodeKeyValue = $node->attr[$att_name];
						}

						if (is_object($debug_object)) {
							$debug_object->debug_log(2,
								'testing node: '
								. $node->tag
								. ' for attribute: '
								. $att_name
								. $att_expr
								. $att_val
								. ' where nodes value is: '
								. $nodeKeyValue
							);
						}

						// If lowercase is set, do a case insensitive test of
						// the value of the selector.
						if ($lowercase) {
							$check = $this->match(
								$att_expr,
								strtolower($att_val),
								strtolower($nodeKeyValue),
								$att_case_sensitivity
							);
						} else {
							$check = $this->match(
								$att_expr,
								$att_val,
								$nodeKeyValue,
								$att_case_sensitivity
							);
						}

						if (is_object($debug_object)) {
							$debug_object->debug_log(2,
								'after match: '
								. ($check ? 'true' : 'false')
							);
						}

						if (!$check) {
							$pass = false;
							break;
						}
					}
			}

			// Found a match. Add to list and clear node
			if ($pass) $ret[$node->_[HDOM_INFO_BEGIN]] = 1;
			unset($node);
		}
		// It's passed by reference so this is actually what this function returns.
		if (is_object($debug_object)) {
			$debug_object->debug_log(1, 'EXIT - ret: ', $ret);
		}
	}

	/**
	 * Match value and pattern for a given CSS expression
	 *
	 * **Supported Expressions**
	 *
	 * | Expression | Description
	 * | ---------- | -----------
	 * | `=`        | $value and $pattern must be equal
	 * | `!=`       | $value and $pattern must not be equal
	 * | `^=`       | $value must start with $pattern
	 * | `$=`       | $value must end with $pattern
	 * | `*=`       | $value must contain $pattern
	 *
	 * @param string $exp The expression.
	 * @param string $pattern The pattern
	 * @param string $value The value
	 * @value bool True if $value matches $pattern
	 */
	protected function match($exp, $pattern, $value, $case_sensitivity)
	{
		global $debug_object;
		if (is_object($debug_object)) {$debug_object->debug_log_entry(1);}

		if ($case_sensitivity === 'i') {
			$pattern = strtolower($pattern);
			$value = strtolower($value);
		}

		switch ($exp) {
			case '=':
				return ($value === $pattern);
			case '!=':
				return ($value !== $pattern);
			case '^=':
				return preg_match('/^' . preg_quote($pattern, '/') . '/', $value);
			case '$=':
				return preg_match('/' . preg_quote($pattern, '/') . '$/', $value);
			case '*=':
				return preg_match('/' . preg_quote($pattern, '/') . '/', $value);
			case '|=':
				/**
				 * [att|=val]
				 *
				 * Represents an element with the att attribute, its value
				 * either being exactly "val" or beginning with "val"
				 * immediately followed by "-" (U+002D).
				 */
				return strpos($value, $pattern) === 0;
			case '~=':
				/**
				 * [att~=val]
				 *
				 * Represents an element with the att attribute whose value is a
				 * whitespace-separated list of words, one of which is exactly
				 * "val". If "val" contains whitespace, it will never represent
				 * anything (since the words are separated by spaces). Also if
				 * "val" is the empty string, it will never represent anything.
				 */
				return in_array($pattern, explode(' ', trim($value)), true);
		}
		return false;
	}

	/**
	 * Parse CSS selector
	 *
	 * @param string $selector_string CSS selector string
	 * @return array List of CSS selectors. The format depends on the type of
	 * selector:
	 *
	 * ```php
	 *
	 * array( // list of selectors (each separated by a comma), i.e. 'img, p, div'
	 *   array( // list of combinator selectors, i.e. 'img > p > div'
	 *     array( // selector element
	 *       [0], // (string) The element tag
	 *       [1], // (string) The element id
	 *       [2], // (array<string>) The element classes
	 *       [3], // (array<array<string>>) The list of attributes, each
	 *            // with four elements: name, expression, value, inverted
	 *       [4]  // (string) The selector combinator (' ' | '>' | '+' | '~')
	 *     )
	 *   )
	 * )
	 * ```
	 *
	 * @link https://www.w3.org/TR/selectors/#compound Compound selector
	 */
	protected function parse_selector($selector_string)
	{
		global $debug_object;
		if (is_object($debug_object)) { $debug_object->debug_log_entry(1); }

		/**
		 * Pattern of CSS selectors, modified from mootools (https://mootools.net/)
		 *
		 * Paperg: Add the colon to the attribute, so that it properly finds
		 * <tag attr:ibute="something" > like google does.
		 *
		 * Note: if you try to look at this attribute, you MUST use getAttribute
		 * since $dom->x:y will fail the php syntax check.
		 *
		 * Notice the \[ starting the attribute? and the @? following? This
		 * implies that an attribute can begin with an @ sign that is not
		 * captured. This implies that an html attribute specifier may start
		 * with an @ sign that is NOT captured by the expression. Farther study
		 * is required to determine of this should be documented or removed.
		 *
		 * Matches selectors in this order:
		 *
		 * [0] - full match
		 *
		 * [1] - tag name
		 *     ([\w:\*-]*)
		 *     Matches the tag name consisting of zero or more words, colons,
		 *     asterisks and hyphens.
		 *
		 * [2] - id name
		 *     (?:\#([\w-]+))
		 *     Optionally matches a id name, consisting of an "#" followed by
		 *     the id name (one or more words and hyphens).
		 *
		 * [3] - class names (including dots)
		 *     (?:\.([\w\.-]+))?
		 *     Optionally matches a list of classs, consisting of an "."
		 *     followed by the class name (one or more words and hyphens)
		 *     where multiple classes can be chained (i.e. ".foo.bar.baz")
		 *
		 * [4] - attributes
		 *     ((?:\[@?(?:!?[\w:-]+)(?:(?:[!*^$|~]?=)[\"']?(?:.*?)[\"']?)?(?:\s*?(?:[iIsS])?)?\])+)?
		 *     Optionally matches the attributes list
		 *
		 * [5] - separator
		 *     ([\/, >+~]+)
		 *     Matches the selector list separator
		 */
		// phpcs:ignore Generic.Files.LineLength
		$pattern = "/([\w:\*-]*)(?:\#([\w-]+))?(?:|\.([\w\.-]+))?((?:\[@?(?:!?[\w:-]+)(?:(?:[!*^$|~]?=)[\"']?(?:.*?)[\"']?)?(?:\s*?(?:[iIsS])?)?\])+)?([\/, >+~]+)/is";

		preg_match_all(
			$pattern,
			trim($selector_string) . ' ', // Add final ' ' as pseudo separator
			$matches,
			PREG_SET_ORDER
		);

		if (is_object($debug_object)) {
			$debug_object->debug_log(2, 'Matches Array: ', $matches);
		}

		$selectors = array();
		$result = array();

		foreach ($matches as $m) {
			$m[0] = trim($m[0]);

			// Skip NoOps
			if ($m[0] === '' || $m[0] === '/' || $m[0] === '//') { continue; }

			// Convert to lowercase
			if ($this->dom->lowercase) {
				$m[1] = strtolower($m[1]);
			}

			// Extract classes
			if ($m[3] !== '') { $m[3] = explode('.', $m[3]); }

			/* Extract attributes (pattern based on the pattern above!)

			 * [0] - full match
			 * [1] - attribute name
			 * [2] - attribute expression
			 * [3] - attribute value
			 * [4] - case sensitivity
			 *
			 * Note: Attributes can be negated with a "!" prefix to their name
			 */
			if($m[4] !== '') {
				preg_match_all(
					"/\[@?(!?[\w:-]+)(?:([!*^$|~]?=)[\"']?(.*?)[\"']?)?(?:\s*?([iIsS])?)?\]/is",
					trim($m[4]),
					$attributes,
					PREG_SET_ORDER
				);

				// Replace element by array
				$m[4] = array();

				foreach($attributes as $att) {
					// Skip empty matches
					if(trim($att[0]) === '') { continue; }

					$inverted = (isset($att[1][0]) && $att[1][0] === '!');
					$m[4][] = array(
						$inverted ? substr($att[1], 1) : $att[1], // Name
						(isset($att[2])) ? $att[2] : '', // Expression
						(isset($att[3])) ? $att[3] : '', // Value
						$inverted, // Inverted Flag
						(isset($att[4])) ? strtolower($att[4]) : '', // Case-Sensitivity
					);
				}
			}

			// Sanitize Separator
			if ($m[5] !== '' && trim($m[5]) === '') { // Descendant Separator
				$m[5] = ' ';
			} else { // Other Separator
				$m[5] = trim($m[5]);
			}

			// Clear Separator if it's a Selector List
			if ($is_list = ($m[5] === ',')) { $m[5] = ''; }

			// Remove full match before adding to results
			array_shift($m);
			$result[] = $m;

			if ($is_list) { // Selector List
				$selectors[] = $result;
				$result = array();
			}
		}

		if (count($result) > 0) { $selectors[] = $result; }
		return $selectors;
	}

	function __get($name)
	{
		if (isset($this->attr[$name])) {
			return $this->convert_text($this->attr[$name]);
		}
		switch ($name) {
			case 'outertext': return $this->outertext();
			case 'innertext': return $this->innertext();
			case 'plaintext': return $this->text();
			case 'xmltext': return $this->xmltext();
			default: return array_key_exists($name, $this->attr);
		}
	}

	function __set($name, $value)
	{
		global $debug_object;
		if (is_object($debug_object)) { $debug_object->debug_log_entry(1); }

		switch ($name) {
			case 'outertext': return $this->_[HDOM_INFO_OUTER] = $value;
			case 'innertext':
				if (isset($this->_[HDOM_INFO_TEXT])) {
					return $this->_[HDOM_INFO_TEXT] = $value;
				}
				return $this->_[HDOM_INFO_INNER] = $value;
		}

		if (!isset($this->attr[$name])) {
			$this->_[HDOM_INFO_SPACE][] = array(' ', '', '');
			$this->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
		}

		$this->attr[$name] = $value;
	}

	function __isset($name)
	{
		switch ($name) {
			case 'outertext': return true;
			case 'innertext': return true;
			case 'plaintext': return true;
		}
		//no value attr: nowrap, checked selected...
		return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
	}

	function __unset($name)
	{
		if (isset($this->attr[$name])) { unset($this->attr[$name]); }
	}

	// PaperG - Function to convert the text from one character set to another
	// if the two sets are not the same.
	function convert_text($text)
	{
		global $debug_object;
		if (is_object($debug_object)) { $debug_object->debug_log_entry(1); }

		$converted_text = $text;

		$sourceCharset = '';
		$targetCharset = '';

		if ($this->dom) {
			$sourceCharset = strtoupper($this->dom->_charset);
			$targetCharset = strtoupper($this->dom->_target_charset);
		}

		if (is_object($debug_object)) {
			$debug_object->debug_log(3,
				'source charset: '
				. $sourceCharset
				. ' target charaset: '
				. $targetCharset
			);
		}

		if (!empty($sourceCharset)
			&& !empty($targetCharset)
			&& (strcasecmp($sourceCharset, $targetCharset) != 0)) {
			// Check if the reported encoding could have been incorrect and the text is actually already UTF-8
			if ((strcasecmp($targetCharset, 'UTF-8') == 0)
				&& ($this->is_utf8($text))) {
				$converted_text = $text;
			} else {
				$converted_text = iconv($sourceCharset, $targetCharset, $text);
			}
		}

		// Lets make sure that we don't have that silly BOM issue with any of the utf-8 text we output.
		if ($targetCharset === 'UTF-8') {
			if (substr($converted_text, 0, 3) === "\xef\xbb\xbf") {
				$converted_text = substr($converted_text, 3);
			}

			if (substr($converted_text, -3) === "\xef\xbb\xbf") {
				$converted_text = substr($converted_text, 0, -3);
			}
		}

		return $converted_text;
	}

	/**
	* Returns true if $string is valid UTF-8 and false otherwise.
	*
	* @param mixed $str String to be tested
	* @return boolean
	*/
	static function is_utf8($str)
	{
		$c = 0; $b = 0;
		$bits = 0;
		$len = strlen($str);
		for($i = 0; $i < $len; $i++) {
			$c = ord($str[$i]);
			if($c > 128) {
				if(($c >= 254)) { return false; }
				elseif($c >= 252) { $bits = 6; }
				elseif($c >= 248) { $bits = 5; }
				elseif($c >= 240) { $bits = 4; }
				elseif($c >= 224) { $bits = 3; }
				elseif($c >= 192) { $bits = 2; }
				else { return false; }
				if(($i + $bits) > $len) { return false; }
				while($bits > 1) {
					$i++;
					$b = ord($str[$i]);
					if($b < 128 || $b > 191) { return false; }
					$bits--;
				}
			}
		}
		return true;
	}

	/**
	 * Function to try a few tricks to determine the displayed size of an img on
	 * the page. NOTE: This will ONLY work on an IMG tag. Returns FALSE on all
	 * other tag types.
	 *
	 * @author John Schlick
	 * @version April 19 2012
	 * @return array an array containing the 'height' and 'width' of the image
	 * on the page or -1 if we can't figure it out.
	 */
	function get_display_size()
	{
		global $debug_object;

		$width = -1;
		$height = -1;

		if ($this->tag !== 'img') {
			return false;
		}

		// See if there is aheight or width attribute in the tag itself.
		if (isset($this->attr['width'])) {
			$width = $this->attr['width'];
		}

		if (isset($this->attr['height'])) {
			$height = $this->attr['height'];
		}

		// Now look for an inline style.
		if (isset($this->attr['style'])) {
			// Thanks to user gnarf from stackoverflow for this regular expression.
			$attributes = array();

			preg_match_all(
				'/([\w-]+)\s*:\s*([^;]+)\s*;?/',
				$this->attr['style'],
				$matches,
				PREG_SET_ORDER
			);

			foreach ($matches as $match) {
				$attributes[$match[1]] = $match[2];
			}

			// If there is a width in the style attributes:
			if (isset($attributes['width']) && $width == -1) {
				// check that the last two characters are px (pixels)
				if (strtolower(substr($attributes['width'], -2)) === 'px') {
					$proposed_width = substr($attributes['width'], 0, -2);
					// Now make sure that it's an integer and not something stupid.
					if (filter_var($proposed_width, FILTER_VALIDATE_INT)) {
						$width = $proposed_width;
					}
				}
			}

			// If there is a width in the style attributes:
			if (isset($attributes['height']) && $height == -1) {
				// check that the last two characters are px (pixels)
				if (strtolower(substr($attributes['height'], -2)) == 'px') {
					$proposed_height = substr($attributes['height'], 0, -2);
					// Now make sure that it's an integer and not something stupid.
					if (filter_var($proposed_height, FILTER_VALIDATE_INT)) {
						$height = $proposed_height;
					}
				}
			}

		}

		// Future enhancement:
		// Look in the tag to see if there is a class or id specified that has
		// a height or width attribute to it.

		// Far future enhancement
		// Look at all the parent tags of this image to see if they specify a
		// class or id that has an img selector that specifies a height or width
		// Note that in this case, the class or id will have the img subselector
		// for it to apply to the image.

		// ridiculously far future development
		// If the class or id is specified in a SEPARATE css file thats not on
		// the page, go get it and do what we were just doing for the ones on
		// the page.

		$result = array(
			'height' => $height,
			'width' => $width
		);

		return $result;
	}

	// camel naming conventions
	function getAllAttributes()
	{
		return $this->attr;
	}

	function getAttribute($name)
	{
		return $this->__get($name);
	}

	function setAttribute($name, $value)
	{
		$this->__set($name, $value);
	}

	function hasAttribute($name)
	{
		return $this->__isset($name);
	}

	function removeAttribute($name)
	{
		$this->__set($name, null);
	}

	function getElementById($id)
	{
		return $this->find("#$id", 0);
	}

	function getElementsById($id, $idx = null)
	{
		return $this->find("#$id", $idx);
	}

	function getElementByTagName($name)
	{
		return $this->find($name, 0);
	}

	function getElementsByTagName($name, $idx = null)
	{
		return $this->find($name, $idx);
	}

	function parentNode()
	{
		return $this->parent();
	}

	function childNodes($idx = -1)
	{
		return $this->children($idx);
	}

	function firstChild()
	{
		return $this->first_child();
	}

	function lastChild()
	{
		return $this->last_child();
	}

	function nextSibling()
	{
		return $this->next_sibling();
	}

	function previousSibling()
	{
		return $this->prev_sibling();
	}

	function hasChildNodes()
	{
		return $this->has_child();
	}

	function nodeName()
	{
		return $this->tag;
	}

	function appendChild($node)
	{
		$node->parent($this);
		return $node;
	}

}

/**
 * simple html dom parser
 *
 * Paperg - in the find routine: allow us to specify that we want case
 * insensitive testing of the value of the selector.
 *
 * Paperg - change $size from protected to public so we can easily access it
 *
 * Paperg - added ForceTagsClosed in the constructor which tells us whether we
 * trust the html or not.  Default is to NOT trust it.
 *
 * @package PlaceLocalInclude
 */
class simple_html_dom
{
	/**
	 * The root node of the document
	 *
	 * @var object
	 */
	public $root = null;

	/**
	 * List of nodes in the current DOM
	 *
	 * @var array
	 */
	public $nodes = array();

	/**
	 * Callback function to run for each element in the DOM.
	 *
	 * @var callable|null
	 */
	public $callback = null;

	/**
	 * Indicates how tags and attributes are matched
	 *
	 * @var bool When set to **true** tags and attributes will be converted to
	 * lowercase before matching.
	 */
	public $lowercase = false;

	/**
	 * Original document size
	 *
	 * Holds the original document size.
	 *
	 * @var int
	 */
	public $original_size;

	/**
	 * Current document size
	 *
	 * Holds the current document size. The document size is determined by the
	 * string length of ({@see simple_html_dom::$doc}).
	 *
	 * _Note_: Using this variable is more efficient than calling `strlen($doc)`
	 *
	 * @var int
	 * */
	public $size;

	/**
	 * Current position in the document
	 *
	 * @var int
	 */
	protected $pos;

	/**
	 * The document
	 *
	 * @var string
	 */
	protected $doc;

	/**
	 * Current character
	 *
	 * Holds the current character at position {@see simple_html_dom::$pos} in
	 * the document {@see simple_html_dom::$doc}
	 *
	 * _Note_: Using this variable is more efficient than calling
	 * `substr($doc, $pos, 1)`
	 *
	 * @var string
	 */
	protected $char;

	protected $cursor;

	/**
	 * Parent node of the next node detected by the parser
	 *
	 * @var object
	 */
	protected $parent;
	protected $noise = array();

	/**
	 * Tokens considered blank in HTML
	 *
	 * @var string
	 */
	protected $token_blank = " \t\r\n";

	/**
	 * Tokens to identify the equal sign for attributes, stopping either at the
	 * closing tag ("/" i.e. "<html />") or the end of an opening tag (">" i.e.
	 * "<html>")
	 *
	 * @var string
	 */
	protected $token_equal = ' =/>';

	/**
	 * Tokens to identify the end of a tag name. A tag name either ends on the
	 * ending slash ("/" i.e. "<html/>") or whitespace ("\s\r\n\t")
	 *
	 * @var string
	 */
	protected $token_slash = " />\r\n\t";

	/**
	 * Tokens to identify the end of an attribute
	 *
	 * @var string
	 */
	protected $token_attr = ' >';

	// Note that this is referenced by a child node, and so it needs to be
	// public for that node to see this information.
	public $_charset = '';
	public $_target_charset = '';

	/**
	 * Innertext for <br> elements
	 *
	 * @var string
	 */
	protected $default_br_text = '';

	/**
	 * Suffix for <span> elements
	 *
	 * @var string
	 */
	public $default_span_text = '';

	/**
	 * Defines a list of self-closing tags (Void elements) according to the HTML
	 * Specification
	 *
	 * _Remarks_:
	 * - Use `isset()` instead of `in_array()` on array elements to boost
	 * performance about 30%
	 * - Sort elements by name for better readability!
	 *
	 * @link https://www.w3.org/TR/html HTML Specification
	 * @link https://www.w3.org/TR/html/syntax.html#void-elements Void elements
	 */
	protected $self_closing_tags = array(
		'area' => 1,
		'base' => 1,
		'br' => 1,
		'col' => 1,
		'embed' => 1,
		'hr' => 1,
		'img' => 1,
		'input' => 1,
		'link' => 1,
		'meta' => 1,
		'param' => 1,
		'source' => 1,
		'track' => 1,
		'wbr' => 1
	);

	/**
	 * Defines a list of tags which - if closed - close all optional closing
	 * elements within if they haven't been closed yet. (So, an element where
	 * neither opening nor closing tag is omissible consistently closes every
	 * optional closing element within)
	 *
	 * _Remarks_:
	 * - Use `isset()` instead of `in_array()` on array elements to boost
	 * performance about 30%
	 * - Sort elements by name for better readability!
	 */
	protected $block_tags = array(
		'body' => 1,
		'div' => 1,
		'form' => 1,
		'root' => 1,
		'span' => 1,
		'table' => 1
	);

	/**
	 * Defines elements whose end tag is omissible.
	 *
	 * * key = Name of an element whose end tag is omissible.
	 * * value = Names of elements whose end tag is omissible, that are closed
	 * by the current element.
	 *
	 * _Remarks_:
	 * - Use `isset()` instead of `in_array()` on array elements to boost
	 * performance about 30%
	 * - Sort elements by name for better readability!
	 *
	 * **Example**
	 *
	 * An `li` element’s end tag may be omitted if the `li` element is immediately
	 * followed by another `li` element. To do that, add following element to the
	 * array:
	 *
	 * ```php
	 * 'li' => array('li'),
	 * ```
	 *
	 * With this, the following two examples are considered equal. Note that the
	 * second example is missing the closing tags on `li` elements.
	 *
	 * ```html
	 * <ul><li>First Item</li><li>Second Item</li></ul>
	 * ```
	 *
	 * <ul><li>First Item</li><li>Second Item</li></ul>
	 *
	 * ```html
	 * <ul><li>First Item<li>Second Item</ul>
	 * ```
	 *
	 * <ul><li>First Item<li>Second Item</ul>
	 *
	 * @var array A two-dimensional array where the key is the name of an
	 * element whose end tag is omissible and the value is an array of elements
	 * whose end tag is omissible, that are closed by the current element.
	 *
	 * @link https://www.w3.org/TR/html/syntax.html#optional-tags Optional tags
	 *
	 * @todo The implementation of optional closing tags doesn't work in all cases
	 * because it only consideres elements who close other optional closing
	 * tags, not taking into account that some (non-blocking) tags should close
	 * these optional closing tags. For example, the end tag for "p" is omissible
	 * and can be closed by an "address" element, whose end tag is NOT omissible.
	 * Currently a "p" element without closing tag stops at the next "p" element
	 * or blocking tag, even if it contains other elements.
	 *
	 * @todo Known sourceforge issue #2977341
	 * B tags that are not closed cause us to return everything to the end of
	 * the document.
	 */
	protected $optional_closing_tags = array(
		// Not optional, see
		// https://www.w3.org/TR/html/textlevel-semantics.html#the-b-element
		'b' => array('b' => 1),
		'dd' => array('dd' => 1, 'dt' => 1),
		// Not optional, see
		// https://www.w3.org/TR/html/grouping-content.html#the-dl-element
		'dl' => array('dd' => 1, 'dt' => 1),
		'dt' => array('dd' => 1, 'dt' => 1),
		'li' => array('li' => 1),
		'optgroup' => array('optgroup' => 1, 'option' => 1),
		'option' => array('optgroup' => 1, 'option' => 1),
		'p' => array('p' => 1),
		'rp' => array('rp' => 1, 'rt' => 1),
		'rt' => array('rp' => 1, 'rt' => 1),
		'td' => array('td' => 1, 'th' => 1),
		'th' => array('td' => 1, 'th' => 1),
		'tr' => array('td' => 1, 'th' => 1, 'tr' => 1),
	);

	function __construct(
		$str = null,
		$lowercase = true,
		$forceTagsClosed = true,
		$target_charset = DEFAULT_TARGET_CHARSET,
		$stripRN = true,
		$defaultBRText = DEFAULT_BR_TEXT,
		$defaultSpanText = DEFAULT_SPAN_TEXT,
		$options = 0)
	{
		if ($str) {
			if (preg_match('/^http:\/\//i', $str) || is_file($str)) {
				$this->load_file($str);
			} else {
				$this->load(
					$str,
					$lowercase,
					$stripRN,
					$defaultBRText,
					$defaultSpanText,
					$options
				);
			}
		}
		// Forcing tags to be closed implies that we don't trust the html, but
		// it can lead to parsing errors if we SHOULD trust the html.
		if (!$forceTagsClosed) {
			$this->optional_closing_array = array();
		}

		$this->_target_charset = $target_charset;
	}

	function __destruct()
	{
		$this->clear();
	}

	// load html from string
	function load(
		$str,
		$lowercase = true,
		$stripRN = true,
		$defaultBRText = DEFAULT_BR_TEXT,
		$defaultSpanText = DEFAULT_SPAN_TEXT,
		$options = 0)
	{
		global $debug_object;

		// prepare
		$this->prepare($str, $lowercase, $defaultBRText, $defaultSpanText);

		// Per sourceforge http://sourceforge.net/tracker/?func=detail&aid=2949097&group_id=218559&atid=1044037
		// Script tags removal now preceeds style tag removal.
		// strip out <script> tags
		$this->remove_noise("'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
		$this->remove_noise("'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");

		// strip out the \r \n's if we are told to.
		if ($stripRN) {
			$this->doc = str_replace("\r", ' ', $this->doc);
			$this->doc = str_replace("\n", ' ', $this->doc);

			// set the length of content since we have changed it.
			$this->size = strlen($this->doc);
		}

		// strip out cdata
		$this->remove_noise("'<!\[CDATA\[(.*?)\]\]>'is", true);
		// strip out comments
		$this->remove_noise("'<!--(.*?)-->'is");
		// strip out <style> tags
		$this->remove_noise("'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
		$this->remove_noise("'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
		// strip out preformatted tags
		$this->remove_noise("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
		// strip out server side scripts
		$this->remove_noise("'(<\?)(.*?)(\?>)'s", true);

		if($options & HDOM_SMARTY_AS_TEXT) { // Strip Smarty scripts
			$this->remove_noise("'(\{\w)(.*?)(\})'s", true);
		}

		// parsing
		$this->parse();
		// end
		$this->root->_[HDOM_INFO_END] = $this->cursor;
		$this->parse_charset();

		// make load function chainable
		return $this;
	}

	// load html from file
	function load_file()
	{
		$args = func_get_args();

		if(($doc = call_user_func_array('file_get_contents', $args)) !== false) {
			$this->load($doc, true);
		} else {
			return false;
		}
	}

	/**
	 * Set the callback function
	 *
	 * @param callable $function_name Callback function to run for each element
	 * in the DOM.
	 * @return void
	 */
	function set_callback($function_name)
	{
		$this->callback = $function_name;
	}

	/**
	 * Remove callback function
	 *
	 * @return void
	 */
	function remove_callback()
	{
		$this->callback = null;
	}

	// save dom as string
	function save($filepath = '')
	{
		$ret = $this->root->innertext();
		if ($filepath !== '') { file_put_contents($filepath, $ret, LOCK_EX); }
		return $ret;
	}

	// find dom node by css selector
	// Paperg - allow us to specify that we want case insensitive testing of the value of the selector.
	function find($selector, $idx = null, $lowercase = false)
	{
		return $this->root->find($selector, $idx, $lowercase);
	}

	// clean up memory due to php5 circular references memory leak...
	function clear()
	{
		foreach ($this->nodes as $n) {
			$n->clear(); $n = null;
		}

		// This add next line is documented in the sourceforge repository.
		// 2977248 as a fix for ongoing memory leaks that occur even with the
		// use of clear.
		if (isset($this->children)) {
			foreach ($this->children as $n) {
				$n->clear(); $n = null;
			}
		}

		if (isset($this->parent)) {
			$this->parent->clear();
			unset($this->parent);
		}

		if (isset($this->root)) {
			$this->root->clear();
			unset($this->root);
		}

		unset($this->doc);
		unset($this->noise);
	}

	function dump($show_attr = true)
	{
		$this->root->dump($show_attr);
	}

	// prepare HTML data and init everything
	protected function prepare(
		$str, $lowercase = true,
		$defaultBRText = DEFAULT_BR_TEXT,
		$defaultSpanText = DEFAULT_SPAN_TEXT)
	{
		$this->clear();

		$this->doc = trim($str);
		$this->size = strlen($this->doc);
		$this->original_size = $this->size; // original size of the html
		$this->pos = 0;
		$this->cursor = 1;
		$this->noise = array();
		$this->nodes = array();
		$this->lowercase = $lowercase;
		$this->default_br_text = $defaultBRText;
		$this->default_span_text = $defaultSpanText;
		$this->root = new simple_html_dom_node($this);
		$this->root->tag = 'root';
		$this->root->_[HDOM_INFO_BEGIN] = -1;
		$this->root->nodetype = HDOM_TYPE_ROOT;
		$this->parent = $this->root;
		if ($this->size > 0) { $this->char = $this->doc[0]; }
	}

	/**
	 * Parse HTML content
	 *
	 * @return bool True on success
	 */
	protected function parse()
	{
		while (true) {
			// Read next tag if there is no text between current position and the
			// next opening tag.
			if (($s = $this->copy_until_char('<')) === '') {
				if($this->read_tag()) {
					continue;
				} else {
					return true;
				}
			}

			// Add a text node for text between tags
			$node = new simple_html_dom_node($this);
			++$this->cursor;
			$node->_[HDOM_INFO_TEXT] = $s;
			$this->link_nodes($node, false);
		}
	}

	// PAPERG - dkchou - added this to try to identify the character set of the
	// page we have just parsed so we know better how to spit it out later.
	// NOTE:  IF you provide a routine called
	// get_last_retrieve_url_contents_content_type which returns the
	// CURLINFO_CONTENT_TYPE from the last curl_exec
	// (or the content_type header from the last transfer), we will parse THAT,
	// and if a charset is specified, we will use it over any other mechanism.
	protected function parse_charset()
	{
		global $debug_object;

		$charset = null;

		if (function_exists('get_last_retrieve_url_contents_content_type')) {
			$contentTypeHeader = get_last_retrieve_url_contents_content_type();
			$success = preg_match('/charset=(.+)/', $contentTypeHeader, $matches);
			if ($success) {
				$charset = $matches[1];
				if (is_object($debug_object)) {
					$debug_object->debug_log(2,
						'header content-type found charset of: '
						. $charset
					);
				}
			}
		}

		if (empty($charset)) {
			$el = $this->root->find('meta[http-equiv=Content-Type]', 0, true);

			if (!empty($el)) {
				$fullvalue = $el->content;
				if (is_object($debug_object)) {
					$debug_object->debug_log(2,
						'meta content-type tag found'
						. $fullvalue
					);
				}

				if (!empty($fullvalue)) {
					$success = preg_match(
						'/charset=(.+)/i',
						$fullvalue,
						$matches
					);

					if ($success) {
						$charset = $matches[1];
					} else {
						// If there is a meta tag, and they don't specify the
						// character set, research says that it's typically
						// ISO-8859-1
						if (is_object($debug_object)) {
							$debug_object->debug_log(2,
								'meta content-type tag couldn\'t be parsed. using iso-8859 default.'
							);
						}

						$charset = 'ISO-8859-1';
					}
				}
			}
		}

		// If we couldn't find a charset above, then lets try to detect one
		// based on the text we got...
		if (empty($charset)) {
			// Use this in case mb_detect_charset isn't installed/loaded on
			// this machine.
			$charset = false;
			if (function_exists('mb_detect_encoding')) {
				// Have php try to detect the encoding from the text given to us.
				$charset = mb_detect_encoding(
					$this->doc . 'ascii',
					$encoding_list = array( 'UTF-8', 'CP1252' )
				);

				if (is_object($debug_object)) {
					$debug_object->debug_log(2, 'mb_detect found: ' . $charset);
				}
			}

			// and if this doesn't work...  then we need to just wrongheadedly
			// assume it's UTF-8 so that we can move on - cause this will
			// usually give us most of what we need...
			if ($charset === false) {
				if (is_object($debug_object)) {
					$debug_object->debug_log(
						2,
						'since mb_detect failed - using default of utf-8'
					);
				}

				$charset = 'UTF-8';
			}
		}

		// Since CP1252 is a superset, if we get one of it's subsets, we want
		// it instead.
		if ((strtolower($charset) == strtolower('ISO-8859-1'))
			|| (strtolower($charset) == strtolower('Latin1'))
			|| (strtolower($charset) == strtolower('Latin-1'))) {

			if (is_object($debug_object)) {
				$debug_object->debug_log(
					2,
					'replacing ' . $charset . ' with CP1252 as its a superset'
				);
			}

			$charset = 'CP1252';
		}

		if (is_object($debug_object)) {
			$debug_object->debug_log(1, 'EXIT - ' . $charset);
		}

		return $this->_charset = $charset;
	}

	/**
	 * Parse tag from current document position.
	 *
	 * @return bool True if a tag was found, false otherwise
	 */
	protected function read_tag()
	{
		// Set end position if no further tags found
		if ($this->char !== '<') {
			$this->root->_[HDOM_INFO_END] = $this->cursor;
			return false;
		}

		$begin_tag_pos = $this->pos;
		$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next

		// end tag
		if ($this->char === '/') {
			$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next

			// Skip whitespace in end tags (i.e. in "</   html>")
			$this->skip($this->token_blank);
			$tag = $this->copy_until_char('>');

			// Skip attributes in end tags
			if (($pos = strpos($tag, ' ')) !== false) {
				$tag = substr($tag, 0, $pos);
			}

			$parent_lower = strtolower($this->parent->tag);
			$tag_lower = strtolower($tag);

			// The end tag is supposed to close the parent tag. Handle situations
			// when it doesn't
			if ($parent_lower !== $tag_lower) {
				// Parent tag does not have to be closed necessarily (optional closing tag)
				// Current tag is a block tag, so it may close an ancestor
				if (isset($this->optional_closing_tags[$parent_lower])
					&& isset($this->block_tags[$tag_lower])) {

					$this->parent->_[HDOM_INFO_END] = 0;
					$org_parent = $this->parent;

					// Traverse ancestors to find a matching opening tag
					// Stop at root node
					while (($this->parent->parent)
						&& strtolower($this->parent->tag) !== $tag_lower
					){
						$this->parent = $this->parent->parent;
					}

					// If we don't have a match add current tag as text node
					if (strtolower($this->parent->tag) !== $tag_lower) {
						$this->parent = $org_parent; // restore origonal parent

						if ($this->parent->parent) {
							$this->parent = $this->parent->parent;
						}

						$this->parent->_[HDOM_INFO_END] = $this->cursor;
						return $this->as_text_node($tag);
					}
				} elseif (($this->parent->parent)
					&& isset($this->block_tags[$tag_lower])
				) {
					// Grandparent exists and current tag is a block tag, so our
					// parent doesn't have an end tag
					$this->parent->_[HDOM_INFO_END] = 0; // No end tag
					$org_parent = $this->parent;

					// Traverse ancestors to find a matching opening tag
					// Stop at root node
					while (($this->parent->parent)
						&& strtolower($this->parent->tag) !== $tag_lower
					) {
						$this->parent = $this->parent->parent;
					}

					// If we don't have a match add current tag as text node
					if (strtolower($this->parent->tag) !== $tag_lower) {
						$this->parent = $org_parent; // restore origonal parent
						$this->parent->_[HDOM_INFO_END] = $this->cursor;
						return $this->as_text_node($tag);
					}
				} elseif (($this->parent->parent)
					&& strtolower($this->parent->parent->tag) === $tag_lower
				) { // Grandparent exists and current tag closes it
					$this->parent->_[HDOM_INFO_END] = 0;
					$this->parent = $this->parent->parent;
				} else { // Random tag, add as text node
					return $this->as_text_node($tag);
				}
			}

			// Set end position of parent tag to current cursor position
			$this->parent->_[HDOM_INFO_END] = $this->cursor;

			if ($this->parent->parent) {
				$this->parent = $this->parent->parent;
			}

			$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
			return true;
		}

		// start tag
		$node = new simple_html_dom_node($this);
		$node->_[HDOM_INFO_BEGIN] = $this->cursor;
		++$this->cursor;
		$tag = $this->copy_until($this->token_slash); // Get tag name
		$node->tag_start = $begin_tag_pos;

		// doctype, cdata & comments...
		// <!DOCTYPE html>
		// <![CDATA[ ... ]]>
		// <!-- Comment -->
		if (isset($tag[0]) && $tag[0] === '!') {
			$node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copy_until_char('>');

			if (isset($tag[2]) && $tag[1] === '-' && $tag[2] === '-') { // Comment ("<!--")
				$node->nodetype = HDOM_TYPE_COMMENT;
				$node->tag = 'comment';
			} else { // Could be doctype or CDATA but we don't care
				$node->nodetype = HDOM_TYPE_UNKNOWN;
				$node->tag = 'unknown';
			}

			if ($this->char === '>') { $node->_[HDOM_INFO_TEXT] .= '>'; }

			$this->link_nodes($node, true);
			$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
			return true;
		}

		// The start tag cannot contain another start tag, if so add as text
		// i.e. "<<html>"
		if ($pos = strpos($tag, '<') !== false) {
			$tag = '<' . substr($tag, 0, -1);
			$node->_[HDOM_INFO_TEXT] = $tag;
			$this->link_nodes($node, false);
			$this->char = $this->doc[--$this->pos]; // prev
			return true;
		}

		// Handle invalid tag names (i.e. "<html#doc>")
		if (!preg_match('/^\w[\w:-]*$/', $tag)) {
			$node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copy_until('<>');

			// Next char is the beginning of a new tag, don't touch it.
			if ($this->char === '<') {
				$this->link_nodes($node, false);
				return true;
			}

			// Next char closes current tag, add and be done with it.
			if ($this->char === '>') { $node->_[HDOM_INFO_TEXT] .= '>'; }
			$this->link_nodes($node, false);
			$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
			return true;
		}

		// begin tag, add new node
		$node->nodetype = HDOM_TYPE_ELEMENT;
		$tag_lower = strtolower($tag);
		$node->tag = ($this->lowercase) ? $tag_lower : $tag;

		// handle optional closing tags
		if (isset($this->optional_closing_tags[$tag_lower])) {
			// Traverse ancestors to close all optional closing tags
			while (isset($this->optional_closing_tags[$tag_lower][strtolower($this->parent->tag)])) {
				$this->parent->_[HDOM_INFO_END] = 0;
				$this->parent = $this->parent->parent;
			}
			$node->parent = $this->parent;
		}

		$guard = 0; // prevent infinity loop

		// [0] Space between tag and first attribute
		$space = array($this->copy_skip($this->token_blank), '', '');

		// attributes
		do {
			// Everything until the first equal sign should be the attribute name
			$name = $this->copy_until($this->token_equal);

			if ($name === '' && $this->char !== null && $space[0] === '') {
				break;
			}

			if ($guard === $this->pos) { // Escape infinite loop
				$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
				continue;
			}

			$guard = $this->pos;

			// handle endless '<'
			// Out of bounds before the tag ended
			if ($this->pos >= $this->size - 1 && $this->char !== '>') {
				$node->nodetype = HDOM_TYPE_TEXT;
				$node->_[HDOM_INFO_END] = 0;
				$node->_[HDOM_INFO_TEXT] = '<' . $tag . $space[0] . $name;
				$node->tag = 'text';
				$this->link_nodes($node, false);
				return true;
			}

			// handle mismatch '<'
			// Attributes cannot start after opening tag
			if ($this->doc[$this->pos - 1] == '<') {
				$node->nodetype = HDOM_TYPE_TEXT;
				$node->tag = 'text';
				$node->attr = array();
				$node->_[HDOM_INFO_END] = 0;
				$node->_[HDOM_INFO_TEXT] = substr(
					$this->doc,
					$begin_tag_pos,
					$this->pos - $begin_tag_pos - 1
				);
				$this->pos -= 2;
				$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
				$this->link_nodes($node, false);
				return true;
			}

			if ($name !== '/' && $name !== '') { // this is a attribute name
				// [1] Whitespace after attribute name
				$space[1] = $this->copy_skip($this->token_blank);

				$name = $this->restore_noise($name); // might be a noisy name

				if ($this->lowercase) { $name = strtolower($name); }

				if ($this->char === '=') { // attribute with value
					$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
					$this->parse_attr($node, $name, $space); // get attribute value
				} else {
					//no value attr: nowrap, checked selected...
					$node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
					$node->attr[$name] = true;
					if ($this->char != '>') { $this->char = $this->doc[--$this->pos]; } // prev
				}

				$node->_[HDOM_INFO_SPACE][] = $space;

				// prepare for next attribute
				$space = array(
					$this->copy_skip($this->token_blank),
					'',
					''
				);
			} else { // no more attributes
				break;
			}
		} while ($this->char !== '>' && $this->char !== '/'); // go until the tag ended

		$this->link_nodes($node, true);
		$node->_[HDOM_INFO_ENDSPACE] = $space[0];

		// handle empty tags (i.e. "<div/>")
		if ($this->copy_until_char('>') === '/') {
			$node->_[HDOM_INFO_ENDSPACE] .= '/';
			$node->_[HDOM_INFO_END] = 0;
		} else {
			// reset parent
			if (!isset($this->self_closing_tags[strtolower($node->tag)])) {
				$this->parent = $node;
			}
		}

		$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next

		// If it's a BR tag, we need to set it's text to the default text.
		// This way when we see it in plaintext, we can generate formatting that the user wants.
		// since a br tag never has sub nodes, this works well.
		if ($node->tag === 'br') {
			$node->_[HDOM_INFO_INNER] = $this->default_br_text;
		}

		return true;
	}

	/**
	 * Parse attribute from current document position
	 *
	 * @param object $node Node for the attributes
	 * @param string $name Name of the current attribute
	 * @param array $space Array for spacing information
	 * @return void
	 */
	protected function parse_attr($node, $name, &$space)
	{
		// Per sourceforge: http://sourceforge.net/tracker/?func=detail&aid=3061408&group_id=218559&atid=1044037
		// If the attribute is already defined inside a tag, only pay attention
		// to the first one as opposed to the last one.
		// https://stackoverflow.com/a/26341866
		if (isset($node->attr[$name])) {
			return;
		}

		// [2] Whitespace between "=" and the value
		$space[2] = $this->copy_skip($this->token_blank);

		switch ($this->char) {
			case '"': // value is anything between double quotes
				$node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
				$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
				$node->attr[$name] = $this->restore_noise($this->copy_until_char('"'));
				$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
				break;
			case '\'': // value is anything between single quotes
				$node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_SINGLE;
				$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
				$node->attr[$name] = $this->restore_noise($this->copy_until_char('\''));
				$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
				break;
			default: // value is anything until the first space or end tag
				$node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
				$node->attr[$name] = $this->restore_noise($this->copy_until($this->token_attr));
		}
		// PaperG: Attributes should not have \r or \n in them, that counts as
		// html whitespace.
		$node->attr[$name] = str_replace("\r", '', $node->attr[$name]);
		$node->attr[$name] = str_replace("\n", '', $node->attr[$name]);
		// PaperG: If this is a "class" selector, lets get rid of the preceeding
		// and trailing space since some people leave it in the multi class case.
		if ($name === 'class') {
			$node->attr[$name] = trim($node->attr[$name]);
		}
	}

	/**
	 * Link node to parent node
	 *
	 * @param object $node Node to link to parent
	 * @param bool $is_child True if the node is a child of parent
	 * @return void
	 */
	// link node's parent
	protected function link_nodes(&$node, $is_child)
	{
		$node->parent = $this->parent;
		$this->parent->nodes[] = $node;
		if ($is_child) {
			$this->parent->children[] = $node;
		}
	}

	/**
	 * Add tag as text node to current node
	 *
	 * @param string $tag Tag name
	 * @return bool True on success
	 */
	protected function as_text_node($tag)
	{
		$node = new simple_html_dom_node($this);
		++$this->cursor;
		$node->_[HDOM_INFO_TEXT] = '</' . $tag . '>';
		$this->link_nodes($node, false);
		$this->char = (++$this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
		return true;
	}

	/**
	 * Seek from the current document position to the first occurrence of a
	 * character not defined by the provided string. Update the current document
	 * position to the new position.
	 *
	 * @param string $chars A string containing every allowed character.
	 * @return void
	 */
	protected function skip($chars)
	{
		$this->pos += strspn($this->doc, $chars, $this->pos);
		$this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
	}

	/**
	 * Copy substring from the current document position to the first occurrence
	 * of a character not defined by the provided string.
	 *
	 * @param string $chars A string containing every allowed character.
	 * @return string Substring from the current document position to the first
	 * occurrence of a character not defined by the provided string.
	 */
	protected function copy_skip($chars)
	{
		$pos = $this->pos;
		$len = strspn($this->doc, $chars, $pos);
		$this->pos += $len;
		$this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
		if ($len === 0) { return ''; }
		return substr($this->doc, $pos, $len);
	}

	/**
	 * Copy substring from the current document position to the first occurrence
	 * of any of the provided characters.
	 *
	 * @param string $chars A string containing every character to stop at.
	 * @return string Substring from the current document position to the first
	 * occurrence of any of the provided characters.
	 */
	protected function copy_until($chars)
	{
		$pos = $this->pos;
		$len = strcspn($this->doc, $chars, $pos);
		$this->pos += $len;
		$this->char = ($this->pos < $this->size) ? $this->doc[$this->pos] : null; // next
		return substr($this->doc, $pos, $len);
	}

	/**
	 * Copy substring from the current document position to the first occurrence
	 * of the provided string.
	 *
	 * @param string $char The string to stop at.
	 * @return string Substring from the current document position to the first
	 * occurrence of the provided string.
	 */
	protected function copy_until_char($char)
	{
		if ($this->char === null) { return ''; }

		if (($pos = strpos($this->doc, $char, $this->pos)) === false) {
			$ret = substr($this->doc, $this->pos, $this->size - $this->pos);
			$this->char = null;
			$this->pos = $this->size;
			return $ret;
		}

		if ($pos === $this->pos) { return ''; }

		$pos_old = $this->pos;
		$this->char = $this->doc[$pos];
		$this->pos = $pos;
		return substr($this->doc, $pos_old, $pos - $pos_old);
	}

	/**
	 * Remove noise from HTML content
	 *
	 * Noise is stored to {@see simple_html_dom::$noise}
	 *
	 * @param string $pattern The regex pattern used for finding noise
	 * @param bool $remove_tag True to remove the entire match. Default is false
	 * to only remove the captured data.
	 */
	protected function remove_noise($pattern, $remove_tag = false)
	{
		global $debug_object;
		if (is_object($debug_object)) { $debug_object->debug_log_entry(1); }

		$count = preg_match_all(
			$pattern,
			$this->doc,
			$matches,
			PREG_SET_ORDER | PREG_OFFSET_CAPTURE
		);

		for ($i = $count - 1; $i > -1; --$i) {
			$key = '___noise___' . sprintf('% 5d', count($this->noise) + 1000);

			if (is_object($debug_object)) {
				$debug_object->debug_log(2, 'key is: ' . $key);
			}

			$idx = ($remove_tag) ? 0 : 1; // 0 = entire match, 1 = submatch
			$this->noise[$key] = $matches[$i][$idx][0];
			$this->doc = substr_replace($this->doc, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
		}

		// reset the length of content
		$this->size = strlen($this->doc);

		if ($this->size > 0) {
			$this->char = $this->doc[0];
		}
	}

	/**
	 * Restore noise to HTML content
	 *
	 * Noise is restored from {@see simple_html_dom::$noise}
	 *
	 * @param string $text A subset of HTML containing noise
	 * @return string The same content with noise restored
	 */
	function restore_noise($text)
	{
		global $debug_object;
		if (is_object($debug_object)) { $debug_object->debug_log_entry(1); }

		while (($pos = strpos($text, '___noise___')) !== false) {
			// Sometimes there is a broken piece of markup, and we don't GET the
			// pos+11 etc... token which indicates a problem outside of us...

			// todo: "___noise___1000" (or any number with four or more digits)
			// in the DOM causes an infinite loop which could be utilized by
			// malicious software
			if (strlen($text) > $pos + 15) {
				$key = '___noise___'
				. $text[$pos + 11]
				. $text[$pos + 12]
				. $text[$pos + 13]
				. $text[$pos + 14]
				. $text[$pos + 15];

				if (is_object($debug_object)) {
					$debug_object->debug_log(2, 'located key of: ' . $key);
				}

				if (isset($this->noise[$key])) {
					$text = substr($text, 0, $pos)
					. $this->noise[$key]
					. substr($text, $pos + 16);
				} else {
					// do this to prevent an infinite loop.
					$text = substr($text, 0, $pos)
					. 'UNDEFINED NOISE FOR KEY: '
					. $key
					. substr($text, $pos + 16);
				}
			} else {
				// There is no valid key being given back to us... We must get
				// rid of the ___noise___ or we will have a problem.
				$text = substr($text, 0, $pos)
				. 'NO NUMERIC NOISE KEY'
				. substr($text, $pos + 11);
			}
		}
		return $text;
	}

	// Sometimes we NEED one of the noise elements.
	function search_noise($text)
	{
		global $debug_object;
		if (is_object($debug_object)) { $debug_object->debug_log_entry(1); }

		foreach($this->noise as $noiseElement) {
			if (strpos($noiseElement, $text) !== false) {
				return $noiseElement;
			}
		}
	}

	function __toString()
	{
		return $this->root->innertext();
	}

	function __get($name)
	{
		switch ($name) {
			case 'outertext':
				return $this->root->innertext();
			case 'innertext':
				return $this->root->innertext();
			case 'plaintext':
				return $this->root->text();
			case 'charset':
				return $this->_charset;
			case 'target_charset':
				return $this->_target_charset;
		}
	}

	// camel naming conventions
	function childNodes($idx = -1)
	{
		return $this->root->childNodes($idx);
	}

	function firstChild()
	{
		return $this->root->first_child();
	}

	function lastChild()
	{
		return $this->root->last_child();
	}

	function createElement($name, $value = null)
	{
		return @str_get_html("<$name>$value</$name>")->first_child();
	}

	function createTextNode($value)
	{
		return @end(str_get_html($value)->nodes);
	}

	function getElementById($id)
	{
		return $this->find("#$id", 0);
	}

	function getElementsById($id, $idx = null)
	{
		return $this->find("#$id", $idx);
	}

	function getElementByTagName($name)
	{
		return $this->find($name, 0);
	}

	function getElementsByTagName($name, $idx = -1)
	{
		return $this->find($name, $idx);
	}

	function loadFile()
	{
		$args = func_get_args();
		$this->load_file($args);
	}
}
