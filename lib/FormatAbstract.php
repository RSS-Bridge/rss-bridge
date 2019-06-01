<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	https://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

/**
 * An abstract class for format implementations
 *
 * This class implements {@see FormatInterface}
 */
abstract class FormatAbstract implements FormatInterface {

	/** The default charset (UTF-8) */
	const DEFAULT_CHARSET = 'UTF-8';

	/** @var string|null $contentType The content type */
	protected $contentType = null;

	/** @var string $charset The charset */
	protected $charset;

	/** @var array $items The items */
	protected $items;

	/**
	 * @var int $lastModified A timestamp to indicate the last modified time of
	 * the output data.
	 */
	protected $lastModified;

	/** @var array $extraInfos The extra infos */
	protected $extraInfos;

	/**
	 * {@inheritdoc}
	 *
	 * @param string $charset {@inheritdoc}
	 */
	public function setCharset($charset){
		$this->charset = $charset;

		return $this;
	}

	/** {@inheritdoc} */
	public function getCharset(){
		$charset = $this->charset;

		return is_null($charset) ? static::DEFAULT_CHARSET : $charset;
	}

	/**
	 * Set the content type
	 *
	 * @param string $contentType The content type
	 * @return self The format object
	 */
	protected function setContentType($contentType){
		$this->contentType = $contentType;

		return $this;
	}

	/**
	 * Set the last modified time
	 *
	 * @param int $lastModified The last modified time
	 * @return void
	 */
	public function setLastModified($lastModified){
		$this->lastModified = $lastModified;
	}

	/**
	 * Send header with the currently specified content type
	 *
	 * @throws \LogicException if the content type is not set
	 * @throws \LogicException if the content type is not a string
	 *
	 * @return void
	 */
	protected function callContentType(){
		if(empty($this->contentType))
			throw new \LogicException('Content-Type is not set!');

		if(!is_string($this->contentType))
			throw new \LogicException('Content-Type must be a string!');

		header('Content-Type: ' . $this->contentType);
	}

	/** {@inheritdoc} */
	public function display(){
		if ($this->lastModified) {
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $this->lastModified) . 'GMT');
		}
		echo $this->stringify();

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $items {@inheritdoc}
	 */
	public function setItems(array $items){
		$this->items = $items;

		return $this;
	}

	/** {@inheritdoc} */
	public function getItems(){
		if(!is_array($this->items))
			throw new \LogicException('Feed the ' . get_class($this) . ' with "setItems" method before !');

		return $this->items;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $extraInfos {@inheritdoc}
	 */
	public function setExtraInfos(array $extraInfos = array()){
		foreach(array('name', 'uri', 'icon') as $infoName) {
			if(!isset($extraInfos[$infoName])) {
				$extraInfos[$infoName] = '';
			}
		}

		$this->extraInfos = $extraInfos;

		return $this;
	}

	/** {@inheritdoc} */
	public function getExtraInfos(){
		if(is_null($this->extraInfos)) { // No extra info ?
			$this->setExtraInfos(); // Define with default value
		}

		return $this->extraInfos;
	}

	/**
	 * Sanitize HTML while leaving it functional.
	 *
	 * Keeps HTML as-is (with clickable hyperlinks) while reducing annoying and
	 * potentially dangerous things.
	 *
	 * @param string $html The HTML content
	 * @return string The sanitized HTML content
	 *
	 * @todo This belongs into `html.php`
	 * @todo Maybe switch to http://htmlpurifier.org/
	 * @todo Maybe switch to http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php
	 */
	protected function sanitizeHtml($html)
	{
		$html = str_replace('<script', '<&zwnj;script', $html); // Disable scripts, but leave them visible.
		$html = str_replace('<iframe', '<&zwnj;iframe', $html);
		$html = str_replace('<link', '<&zwnj;link', $html);
		// We leave alone object and embed so that videos can play in RSS readers.
		return $html;
	}

	/**
	 * Trim each element of an array
	 *
	 * This function applies `trim()` to all elements in the array, if the element
	 * is a valid string.
	 *
	 * @param array $elements The array to trim
	 * @return array The trimmed array
	 *
	 * @todo This is a utility function that doesn't belong here, find a new home.
	 */
	protected function array_trim($elements){
		foreach($elements as $key => $value) {
			if(is_string($value))
				$elements[$key] = trim($value);
		}
		return $elements;
	}
}
