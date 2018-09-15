<?php
final class BridgeCard {

	private static function buildFormatButtons($formats) {
		$buttons = '';

		foreach($formats as $name) {
			$buttons .= '<button type="submit" name="format" value="'
			. $name
			. '">'
			. $name
			. '</button>'
			. PHP_EOL;
		}

		return $buttons;
	}

	private static function getFormHeader($bridgeName, $isHttps = false) {
		$form = <<<EOD
			<form method="GET" action="?">
				<input type="hidden" name="action" value="display" />
				<input type="hidden" name="bridge" value="{$bridgeName}" />
EOD;

		if(!$isHttps) {
			$form .= '<div class="secure-warning">Warning :
This bridge is not fetching its content through a secure connection</div>';
		}

		return $form;
	}

	private static function getForm($bridgeName,
	$formats,
	$isActive = false,
	$isHttps = false,
	$parameterName = '',
	$parameters = array()) {
		$form = BridgeCard::getFormHeader($bridgeName, $isHttps);

		if(count($parameters) > 0) {

			$form .= '<div class="parameters">';

			foreach($parameters as $id => $inputEntry) {
				if(!isset($inputEntry['exampleValue']))
					$inputEntry['exampleValue'] = '';

				if(!isset($inputEntry['defaultValue']))
					$inputEntry['defaultValue'] = '';

				$idArg = 'arg-'
					. urlencode($bridgeName)
					. '-'
					. urlencode($parameterName)
					. '-'
					. urlencode($id);

				$form .= '<label for="'
					. $idArg
					. '">'
					. filter_var($inputEntry['name'], FILTER_SANITIZE_STRING)
					. '</label>'
					. PHP_EOL;

				if(!isset($inputEntry['type']) || $inputEntry['type'] === 'text') {
					$form .= BridgeCard::getTextInput($inputEntry, $idArg, $id);
				} elseif($inputEntry['type'] === 'number') {
					$form .= BridgeCard::getNumberInput($inputEntry, $idArg, $id);
				} else if($inputEntry['type'] === 'list') {
					$form .= BridgeCard::getListInput($inputEntry, $idArg, $id);
				} elseif($inputEntry['type'] === 'checkbox') {
					$form .= BridgeCard::getCheckboxInput($inputEntry, $idArg, $id);
				}
			}

			$form .= '</div>';

		}

		if($isActive) {
			$form .= BridgeCard::buildFormatButtons($formats);
		} else {
			$form .= '<span style="font-weight: bold;">Inactive</span>';
		}

		return $form . '</form>' . PHP_EOL;
	}

	private static function getInputAttributes($entry) {
		$retVal = '';

		if(isset($entry['required']) && $entry['required'] === true)
			$retVal .= ' required';

		if(isset($entry['pattern']))
			$retVal .= ' pattern="' . $entry['pattern'] . '"';

		if(isset($entry['title']))
			$retVal .= ' title="' . filter_var($entry['title'], FILTER_SANITIZE_STRING) . '"';

		return $retVal;
	}

	private static function getTextInput($entry, $id, $name) {
		return '<input '
		. BridgeCard::getInputAttributes($entry)
		. ' id="'
		. $id
		. '" type="text" value="'
		. filter_var($entry['defaultValue'], FILTER_SANITIZE_STRING)
		. '" placeholder="'
		. filter_var($entry['exampleValue'], FILTER_SANITIZE_STRING)
		. '" name="'
		. $name
		. '" />'
		. PHP_EOL;
	}

	private static function getNumberInput($entry, $id, $name) {
		return '<input '
		. BridgeCard::getInputAttributes($entry)
		. ' id="'
		. $id
		. '" type="number" value="'
		. filter_var($entry['defaultValue'], FILTER_SANITIZE_NUMBER_INT)
		. '" placeholder="'
		. filter_var($entry['exampleValue'], FILTER_SANITIZE_NUMBER_INT)
		. '" name="'
		. $name
		. '" />'
		. PHP_EOL;
	}

	private static function getListInput($entry, $id, $name) {
		$list = '<select '
		. BridgeCard::getInputAttributes($entry)
		. ' id="'
		. $id
		. '" name="'
		. $name
		. '" >';

		foreach($entry['values'] as $name => $value) {
			if(is_array($value)) {
				$list .= '<optgroup label="' . htmlentities($name) . '">';
				foreach($value as $subname => $subvalue) {
					if($entry['defaultValue'] === $subname
						|| $entry['defaultValue'] === $subvalue) {
						$list .= '<option value="'
							. $subvalue
							. '" selected>'
							. $subname
							. '</option>';
					} else {
						$list .= '<option value="'
							. $subvalue
							. '">'
							. $subname
							. '</option>';
					}
				}
				$list .= '</optgroup>';
			} else {
				if($entry['defaultValue'] === $name
					|| $entry['defaultValue'] === $value) {
					$list .= '<option value="'
						. $value
						. '" selected>'
						. $name
						. '</option>';
				} else {
					$list .= '<option value="'
						. $value
						. '">'
						. $name
						. '</option>';
				}
			}
		}

		$list .= '</select>';

		return $list;
	}

	private static function getCheckboxInput($entry, $id, $name) {
		return '<input '
		. BridgeCard::getInputAttributes($entry)
		. ' id="'
		. $id
		. '" type="checkbox" name="'
		. $name
		. '" '
		. ($entry['defaultValue'] === 'checked' ?: '')
		. ' />'
		. PHP_EOL;
	}

	static function displayBridgeCard($bridgeName, $formats, $isActive = true){

		$bridge = Bridge::create($bridgeName);

		if($bridge == false)
			return '';

		$isHttps = strpos($bridge->getURI(), 'https') === 0;

		$uri = $bridge->getURI();
		$name = $bridge->getName();
		$icon = $bridge->getIcon();
		$description = $bridge->getDescription();
		$parameters = $bridge->getParameters();

		if(defined('PROXY_URL') && PROXY_BYBRIDGE) {
			$parameters['global']['_noproxy'] = array(
				'name' => 'Disable proxy (' . ((defined('PROXY_NAME') && PROXY_NAME) ? PROXY_NAME : PROXY_URL) . ')',
				'type' => 'checkbox'
			);
		}

		if(CUSTOM_CACHE_TIMEOUT) {
			$parameters['global']['_cache_timeout'] = array(
				'name' => 'Cache timeout in seconds',
				'type' => 'number',
				'defaultValue' => $bridge->getCacheTimeout()
			);
		}

		$card = <<<CARD
			<section id="bridge-{$bridgeName}" data-ref="{$bridgeName}">
				<h2><a href="{$uri}">{$name}</a></h2>
				<p class="description">{$description}</p>
				<input type="checkbox" class="showmore-box" id="showmore-{$bridgeName}" />
				<label class="showmore" for="showmore-{$bridgeName}">Show more</label>
CARD;

		// If we don't have any parameter for the bridge, we print a generic form to load it.
		if(count($parameters) === 0
		|| count($parameters) === 1 && array_key_exists('global', $parameters)) {

			$card .= BridgeCard::getForm($bridgeName, $formats, $isActive, $isHttps);

		} else {

			foreach($parameters as $parameterName => $parameter) {
				if(!is_numeric($parameterName) && $parameterName === 'global')
					continue;

				if(array_key_exists('global', $parameters))
					$parameter = array_merge($parameter, $parameters['global']);

				if(!is_numeric($parameterName))
					$card .= '<h5>' . $parameterName . '</h5>' . PHP_EOL;

				$card .= BridgeCard::getForm($bridgeName, $formats, $isActive, $isHttps, $parameterName, $parameter);
			}

		}

		$card .= '<label class="showless" for="showmore-' . $bridgeName . '">Show less</label>';
		$card .= '<p class="maintainer">' . $bridge->getMaintainer() . '</p>';
		$card .= '</section>';

		return $card;
	}
}
