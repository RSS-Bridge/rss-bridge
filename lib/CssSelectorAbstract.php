<?php

/**
 * An alternative abstract class for bridges utilizing CSS Selectors.
 **/
abstract class CssSelectorAbstract extends UserDefinedAbstract {
	const USER_EXPRESSION_FEED_TITLE = 'title';
	const USER_EXPRESSION_FEED_ICON = 'link[rel="icon"]';

	protected function provideFeedIcon($data) {
		$icon = parent::provideFeedIcon($data);
		if(!$this->isEmpty($icon)) {
			return $icon[0]->href;
		}
	}

	protected function provideFeedTitle($data) {
		$title = parent::provideFeedIcon($data);
		if(!$this->isEmpty($title)) {
			return $title[0]->plaintext;
		}
	}

	/**
	 * This function defines how css selectors are used to subset the data.
	 */
	protected function convertUserQuery($dom, $query, $context) {
		if (!is_null($context)) {
			$dom = $context;
		}

		return $dom->find($query);
	}

	protected function isEmpty($result) {
		return empty($result);
	}

	protected function provideWebsiteData() {
		return str_get_html($this->provideWebsiteContent());
	}

	/**
	 * @param $result
	 * @return string
	 */
	protected function getDataValue($result) {
		return $result;
	}

	protected function formatItemTitle($value) {
		return $value[0]->plaintext;
	}

	protected function formatItemContent($value) {
		return $value[0]->innertext;
	}

	protected function formatItemUri($value) {
		$value = $value[0]->href ?? $value[0]->src ?? $value[0]->plaintext;

		return parent::formatItemUri($value);
	}

	protected function formatItemAuthor($value) {
		return $value[0]->plaintext;
	}


	protected function formatItemTimestamp($value) {
		return strtotime($value[0]->plaintext);
	}

	/**
	 * Formats the enclosures of a feed item. Takes extracted raw enclosures and returns them
	 * formatted as array.
	 * Can be easily overwritten for in case the values need to be transformed into something
	 * else.
	 * @param array|simple_html_dom_node $value
	 * @return array
	 */
	protected function formatItemEnclosures($value) {
		$retVal = array();
		foreach($value as $enclosure) {
			$retVal[] = $this->cleanMediaUrl($enclosure->src);
		}
		return $retVal;
	}

	/**
	 * Formats the categories of a feed item. Takes extracted raw categories and returns them
	 * formatted as array.
	 * Can be easily overwritten for in case the values need to be transformed into something
	 * else.
	 * @param array|simple_html_dom_node $value
	 * @return array
	 */
	protected function formatItemCategories($value) {
		$retVal = array();
		foreach($value as $enclosure) {
			$retVal[] = $enclosure->plaintext;
		}
		return $retVal;
	}

	protected function formatItemUid($value) {
		return empty($value[0]->plaintext) ? null : $value[0]->plaintext;
	}
}
