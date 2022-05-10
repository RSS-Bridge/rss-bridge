<?php

/**
 * An alternative abstract class for bridges utilizing XPath expressions
 *
 * This class is meant as an alternative base class for bridge implementations.
 * It offers preliminary functionality for generating feeds based on XPath
 * expressions.
 * As a minimum, extending classes should define XPath expressions pointing
 * to the feed items contents in the class constants below. In case there is
 * more manual fine tuning required, it offers a bunch of methods which can
 * be overridden, for example in order to specify formatting of field values
 * or more flexible definition of dynamic XPath expressions.
 *
 * This class extends {@see BridgeAbstract}, which means it incorporates and
 * extends all of its functionality.
 **/
abstract class XPathAbstract extends UserDefinedAbstract {
	/**
	 * This function defines how xpath expressions are used to subset the data.
	 */
	protected function convertUserQuery($xpath, $query, $context) {
		return @$xpath->evaluate($query, $context);
	}

	protected function isEmpty($typedResult) {
		if ($typedResult === false
			|| ($typedResult instanceof DOMNodeList && count($typedResult) === 0)
			|| (is_string($typedResult) && strlen(trim($typedResult)) === 0)) {
			return true;
		}
		return false;
	}

	protected function provideWebsiteData() {
		$webPageHtml = new DOMDocument();
		libxml_use_internal_errors(true);
		$webPageHtml->loadHTML($this->provideWebsiteContent());
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		return new DOMXPath($webPageHtml);
	}

	/**
	 * @param $typedResult
	 * @return string
	 */
	protected function getDataValue($typedResult) {
		if($typedResult instanceof DOMNodeList) {
			$item = $typedResult->item(0);
			if ($item instanceof DOMElement) {
				return trim($item->nodeValue);
			} elseif ($item instanceof DOMAttr) {
				return trim($item->value);
			} elseif ($item instanceof DOMText) {
				return trim($item->wholeText);
			}
		} elseif(is_string($typedResult) && strlen($typedResult) > 0) {
			return trim($typedResult);
		}
		returnServerError('Unknown type of XPath expression result.');
	}

	/**
	 * Formats the enclosures of a feed item. Takes extracted raw enclosures and returns them
	 * formatted as array.
	 * Can be easily overwritten for in case the values need to be transformed into something
	 * else.
	 * @param string $value
	 * @return array
	 */
	protected function formatItemEnclosures($value) {
		return array($this->cleanMediaUrl($value));
	}

	/**
	 * Formats the categories of a feed item. Takes extracted raw categories and returns them
	 * formatted as array.
	 * Can be easily overwritten for in case the values need to be transformed into something
	 * else.
	 * @param string $value
	 * @return array
	 */
	protected function formatItemCategories($value) {
		return array($value);
	}
}
