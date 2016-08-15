<?php
class HTMLUtils {

	public static function displayBridgeCard($bridgeName, $formats, $isActive = true){
		$bridgeElement = Bridge::create($bridgeName);

		if($bridgeElement == false)
			return "";

		$bridgeElement->loadMetadatas();

		$name = '<a href="' . $bridgeElement->uri . '">' . $bridgeElement->name . '</a>';
		$description = $bridgeElement->description;

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
		if(count($bridgeElement->parameters) == 0) {

			$card .= HTMLUtils::getFormHeader($bridgeName);

			if ($isActive){
				$card .= HTMLUtils::getHelperButtonsFormat($formats);
			} else {
				$card .= '<span style="font-weight: bold;">Inactive</span>';
			}

			$card .= '</form>' . PHP_EOL;
		}

		$hasGlobalParameter = array_key_exists('global', $bridgeElement->parameters);

		if($hasGlobalParameter)
			$globalParameters = json_decode($bridgeElement->parameters['global'], true);
		
		foreach($bridgeElement->parameters as $parameterName => $parameter){
			$parameter = json_decode($parameter, true);

			if(!is_numeric($parameterName) && $parameterName == 'global')
				continue;
			
			if($hasGlobalParameter)
				$parameter = array_merge($parameter, $globalParameters);

			if(!is_numeric($parameterName))
				$card .= '<h5>' . $parameterName . '</h5>' . PHP_EOL;

			$card .= HTMLUtils::getFormHeader($bridgeName);

			foreach($parameter as $inputEntry) {
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

				$idArg = 'arg-' . urlencode($bridgeName) . '-' . urlencode($parameterName) . '-' . urlencode($inputEntry['identifier']);
				$card .= '<label for="' . $idArg . '">' . $inputEntry['name'] . ' : </label>' . PHP_EOL;

				if(!isset($inputEntry['type']) || $inputEntry['type'] == 'text') {
					$card .= '<input ' . $additionalInfoString . ' id="' . $idArg . '" type="text" value="' . $inputEntry['defaultValue'] . '" placeholder="' . $inputEntry['exampleValue'] . '" name="' . $inputEntry['identifier'] . '" /><br />' . PHP_EOL;
				} else if($inputEntry['type'] == 'number') {
					$card .= '<input ' . $additionalInfoString . ' id="' . $idArg . '" type="number" value="' . $inputEntry['defaultValue'] . '" placeholder="' . $inputEntry['exampleValue'] . '" name="' . $inputEntry['identifier'] . '" /><br />' . PHP_EOL;
				} else if($inputEntry['type'] == 'list') {
					$card .= '<select ' . $additionalInfoString . ' id="' . $idArg . '" name="' . $inputEntry['identifier'] . '" >';
					
					foreach($inputEntry['values'] as $listValues) {
						if($inputEntry['defaultValue'] === $listValues['name'] || $inputEntry['defaultValue'] === $listValues['value'])
							$card .= '<option value="' . $listValues['value'] . '" selected>' . $listValues['name'] . '</option>';
						else
							$card .= '<option value="' . $listValues['value'] . '">' . $listValues['name'] . '</option>';
					}

					$card .= '</select><br >';
				} else if($inputEntry['type'] == 'checkbox') {
					if($inputEntry['defaultValue'] === 'checked')
						$card .= '<input ' . $additionalInfoString . ' id="' . $idArg . '" type="checkbox" name="' . $inputEntry['identifier'] . '" checked /><br />' . PHP_EOL;
					else
						$card .= '<input ' . $additionalInfoString . ' id="' . $idArg . '" type="checkbox" name="' . $inputEntry['identifier'] . '" /><br />' . PHP_EOL;
				}
			}

			if ($isActive){
				$card .= HTMLUtils::getHelperButtonsFormat($formats);
			} else {
				$card .= '<span style="font-weight: bold;">Inactive</span>';
			}
			
			$card .= '</form>' . PHP_EOL;
		}

		$card .= '<label class="showless" for="showmore-' . $bridgeName . '">Show less</label>';
		$card .= '<p class="maintainer">' . $bridgeElement->maintainer . '</p>';
		$card .= '</section>';

		return $card;
	}

	private static function getHelperButtonsFormat($formats){
		$buttons = '';

		foreach( $formats as $name => $infos ){
			if ( isset($infos['name']) )
				$buttons .= '<button type="submit" name="format" value="' . $name . '">' . $infos['name'] . '</button>' . PHP_EOL;
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

	public function __construct($tags_to_remove = null, $kept_attributes = null, $only_keep_text = null) {
		$this->tagsToRemove = $tags_to_remove == null ? HTMLSanitizer::$DEFAULT_CLEAR_TAGS : $tags_to_remove;
		$this->keptAttributes = $kept_attributes == null ? HTMLSanitizer::$KEPT_ATTRIBUTES : $kept_attributes;
		$this->onlyKeepText = $only_keep_text == null ? HTMLSanitizer::$ONLY_TEXT : $only_keep_text;
	}

	public function sanitize($textToSanitize) {
		$htmlContent = str_get_html($textToSanitize);

		foreach($htmlContent->find('*[!b38fd2b1fe7f4747d6b1c1254ccd055e]') as $element) {
			if(in_array($element->tag, $this->onlyKeepText)) {
				$element->outertext = $element->plaintext;
			} else if(in_array($element->tag, $this->tagsToRemove)) {
				$element->outertext = '';
			} else {
				foreach($element->getAllAttributes() as $attributeName => $attribute) {
					if(!in_array($attributeName, $this->keptAttributes)) 
						$element->removeAttribute($attributeName);
				}
			}
		}

		return $htmlContent;
	}

	public static function defaultImageSrcTo($content, $server) {
		foreach($content->find('img') as $image) {
			if(strpos($image->src, "http") == NULL && strpos($image->src, "//") == NULL && strpos($image->src, "data:") == NULL)
				$image->src = $server.$image->src;
		}
		return $content;
	}
}
