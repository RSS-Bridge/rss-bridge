<?php

abstract class JsonAbstract extends UserDefinedAbstract {

	const USER_EXPRESSION_DELIMITER = '.';

	protected function provideWebsiteContent() {
		$header = array(
			'Accept: application/json'
		);
		return getContents($this->feedUri, $header);
	}

	protected function provideWebsiteData() {
		return json_decode($this->provideWebsiteContent(), true);
	}

	protected function getExpressionDelimiter() {
		return static::USER_EXPRESSION_DELIMITER;
	}

	protected function convertUserQuery($json, $query, $context) {
		if (empty($query)) {
			return null;
		}

		if (!is_null($context)) {
			$json = $context;
		}

		foreach(explode($this->getExpressionDelimiter(), $query) as $key) {
			if (empty($key)) {
				return $json;
			}
			$json = $json[$key] ?? null;
		}
		return $json;
	}

	protected function isEmpty($result) {
		return is_null($result)
			|| (is_array($result) && count($result) === 0)
			|| (is_string($result) && strlen($result) === 0);
	}

	protected function getDataValue($result) {
		return $result;
	}

	protected function formatItemCategories($value) {
		if (is_array($value)) {
			return $value;
		}
		return array($value);
	}

	protected function formatItemEnclosures($value) {
		if (is_array($value)) {
			return $value;
		}
		return array($value);
	}
}
