<?php
class HTMLUtils {
	public static function displayBridgeCard($bridgeName, $formats, $isActive = true){
		$bridgeElement = Bridge::create($bridgeName);
		$bridgeClass = $bridgeName . 'Bridge';

		if($bridgeElement == false)
			return "";

		$name = '<a href="' . $bridgeClass::URI . '">' . $bridgeClass::NAME . '</a>';
		$description = $bridgeClass::DESCRIPTION;

		$card = <<<CARD
		<section id="bridge-{$bridgeName}" data-ref="{$bridgeName}">
			<h2>{$name}</h2>
			<p class="description">
				{$description}
			</p>
			<input type="checkbox" class="showmore-box" id="showmore-{$bridgeName}" />
			<label class="showmore" for="showmore-{$bridgeName}">Show more</label>
CARD;

		// If we don't have any parameter for the bridge, we print a generic form to load it.
		if(count($bridgeClass::PARAMETERS) == 0){

			$card .= HTMLUtils::getFormHeader($bridgeName);

			if($isActive){
				if(defined('PROXY_URL') && PROXY_BYBRIDGE){
					$idArg = 'arg-'
					. urlencode($bridgeName)
					. '-'
					. urlencode('proxyoff')
					. '-'
					. urlencode('_noproxy');

					$card .= '<input id="'
					. $idArg
					. '" type="checkbox" name="_noproxy" />'
					. PHP_EOL;

					$card .= '<label for="'
					. $idArg
					. '">Disable proxy ('
					. ((defined('PROXY_NAME') && PROXY_NAME) ? PROXY_NAME : PROXY_URL)
					. ')</label><br />'
					. PHP_EOL;
				}

				$card .= HTMLUtils::getHelperButtonsFormat($formats);
			} else {
				$card .= '<span style="font-weight: bold;">Inactive</span>';
			}

			$card .= '</form>' . PHP_EOL;
		}

		$hasGlobalParameter = array_key_exists('global', $bridgeClass::PARAMETERS);

		if($hasGlobalParameter){
			$globalParameters = $bridgeClass::PARAMETERS['global'];
		}

		foreach($bridgeClass::PARAMETERS as $parameterName => $parameter){
			if(!is_numeric($parameterName) && $parameterName == 'global')
				continue;

			if($hasGlobalParameter)
				$parameter = array_merge($parameter, $globalParameters);

			if(!is_numeric($parameterName))
				$card .= '<h5>' . $parameterName . '</h5>' . PHP_EOL;

			$card .= HTMLUtils::getFormHeader($bridgeName);

			foreach($parameter as $id => $inputEntry){
				$additionalInfoString = '';

				if(isset($inputEntry['required']) && $inputEntry['required'] === true)
					$additionalInfoString .= ' required';

				if(isset($inputEntry['pattern']))
					$additionalInfoString .= ' pattern="' . $inputEntry['pattern'] . '"';

				if(isset($inputEntry['title']))
					$additionalInfoString .= ' title="' . $inputEntry['title'] . '"';

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

				$card .= '<label for="'
				. $idArg
				. '">'
				. $inputEntry['name']
				. ' : </label>'
				. PHP_EOL;

				if(!isset($inputEntry['type']) || $inputEntry['type'] == 'text'){
					$card .= '<input '
					. $additionalInfoString
					. ' id="'
					. $idArg
					. '" type="text" value="'
					. $inputEntry['defaultValue']
					. '" placeholder="'
					. $inputEntry['exampleValue']
					. '" name="'
					. $id
					. '" /><br />'
					. PHP_EOL;
				} elseif($inputEntry['type'] == 'number'){
					$card .= '<input '
					. $additionalInfoString
					. ' id="'
					. $idArg
					. '" type="number" value="'
					. $inputEntry['defaultValue']
					. '" placeholder="'
					. $inputEntry['exampleValue']
					. '" name="'
					. $id
					. '" /><br />'
					. PHP_EOL;
				} else if($inputEntry['type'] == 'list'){
					$card .= '<select '
					. $additionalInfoString
					. ' id="'
					. $idArg
					. '" name="'
					. $id
					. '" >';

					foreach($inputEntry['values'] as $name => $value){
						if(is_array($value)){
							$card .= '<optgroup label="' . htmlentities($name) . '">';
							foreach($value as $subname => $subvalue){
								if($inputEntry['defaultValue'] === $subname
									|| $inputEntry['defaultValue'] === $subvalue){
									$card .= '<option value="'
									. $subvalue
									. '" selected>'
									. $subname
									. '</option>';
								} else {
									$card .= '<option value="'
									. $subvalue
									. '">'
									. $subname
									. '</option>';
								}
							}
							$card .= '</optgroup>';
						} else {
							if($inputEntry['defaultValue'] === $name
							|| $inputEntry['defaultValue'] === $value){
								$card .= '<option value="'
								. $value
								. '" selected>'
								. $name
								. '</option>';
							} else {
								$card .= '<option value="'
								. $value
								. '">'
								. $name
								. '</option>';
							}
						}
					}
					$card .= '</select><br >';
				} elseif($inputEntry['type'] == 'checkbox'){
					if($inputEntry['defaultValue'] === 'checked')
						$card .= '<input '
						. $additionalInfoString
						. ' id="'
						. $idArg
						. '" type="checkbox" name="'
						. $id
						. '" checked /><br />'
						. PHP_EOL;
					else
						$card .= '<input '
						. $additionalInfoString
						. ' id="'
						. $idArg
						. '" type="checkbox" name="'
						. $id
						. '" /><br />'
						. PHP_EOL;
				}
			}

			if($isActive){
				if(defined('PROXY_URL') && PROXY_BYBRIDGE){
					$idArg = 'arg-'
					. urlencode($bridgeName)
					. '-'
					. urlencode('proxyoff')
					. '-'
					. urlencode('_noproxy');

					$card .= '<input id="'
					. $idArg
					. '" type="checkbox" name="_noproxy" />'
					. PHP_EOL;

					$card .= '<label for="'
					. $idArg
					. '">Disable proxy ('
					. ((defined('PROXY_NAME') && PROXY_NAME) ? PROXY_NAME : PROXY_URL)
					. ')</label><br />'
					. PHP_EOL;
				}
				$card .= HTMLUtils::getHelperButtonsFormat($formats);
			} else {
				$card .= '<span style="font-weight: bold;">Inactive</span>';
			}
			$card .= '</form>' . PHP_EOL;
		}

		$card .= '<label class="showless" for="showmore-' . $bridgeName . '">Show less</label>';
		$card .= '<p class="maintainer">' . $bridgeClass::MAINTAINER . '</p>';
		$card .= '</section>';

		return $card;
	}

	private static function getHelperButtonsFormat($formats){
		$buttons = '';
		foreach($formats as $name){
			$buttons .= '<button type="submit" name="format" value="'
			. $name
			. '">'
			. $name
			. '</button>'
			. PHP_EOL;
		}

		return $buttons;
	}

	private static function getFormHeader($bridge){
		return <<<EOD
			<form method="GET" action="?">
				<input type="hidden" name="action" value="display" />
				<input type="hidden" name="bridge" value="{$bridge}" />
EOD;
	}
}

class HTMLSanitizer {

	var $tagsToRemove;
	var $keptAttributes;
	var $onlyKeepText;

	public static $DEFAULT_CLEAR_TAGS = ["script", "iframe", "input", "form"];
	public static $KEPT_ATTRIBUTES = ["title", "href", "src"];
	public static $ONLY_TEXT = [];

	public function __construct($tags_to_remove = null
	, $kept_attributes = null
	, $only_keep_text = null){
		$this->tagsToRemove = is_null($tags_to_remove) ? HTMLSanitizer::$DEFAULT_CLEAR_TAGS : $tags_to_remove;
		$this->keptAttributes = is_null($kept_attributes) ? HTMLSanitizer::$KEPT_ATTRIBUTES : $kept_attributes;
		$this->onlyKeepText = is_null($only_keep_text) ? HTMLSanitizer::$ONLY_TEXT : $only_keep_text;
	}

	public function sanitize($textToSanitize){
		$htmlContent = str_get_html($textToSanitize);

		foreach($htmlContent->find('*[!b38fd2b1fe7f4747d6b1c1254ccd055e]') as $element){
			if(in_array($element->tag, $this->onlyKeepText)){
				$element->outertext = $element->plaintext;
			} elseif(in_array($element->tag, $this->tagsToRemove)){
				$element->outertext = '';
			} else {
				foreach($element->getAllAttributes() as $attributeName => $attribute){
					if(!in_array($attributeName, $this->keptAttributes))
						$element->removeAttribute($attributeName);
				}
			}
		}

		return $htmlContent;
	}

	public static function defaultImageSrcTo($content, $server){
		foreach($content->find('img') as $image){
			if(is_null(strpos($image->src, "http"))
			&& is_null(strpos($image->src, "//"))
			&& is_null(strpos($image->src, "data:")))
				$image->src = $server . $image->src;
		}
		return $content;
	}
}
