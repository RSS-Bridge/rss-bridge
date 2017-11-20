<?php
function displayBridgeCard($bridgeName, $formats, $isActive = true){

	$getHelperButtonsFormat = function($formats){
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
	};

	$getFormHeader = function($bridgeName){
		return <<<EOD
			<form method="GET" action="?">
				<input type="hidden" name="action" value="display" />
				<input type="hidden" name="bridge" value="{$bridgeName}" />
EOD;
	};

	$bridge = Bridge::create($bridgeName);

	if($bridge == false)
		return "";

	$HTTPSWarning = '';
	if(strpos($bridge->getURI(), 'https') !== 0) {

		$HTTPSWarning = '<div class="secure-warning">Warning :
						This bridge is not fetching its content through a secure connection</div>';

	}

	$name = '<a href="' . $bridge->getURI() . '">' . $bridge->getName() . '</a>';
	$description = $bridge->getDescription();

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
	if(count($bridge->getParameters()) == 0) {

		$card .= $getFormHeader($bridgeName);
		$card .= $HTTPSWarning;

		if($isActive) {
			if(defined('PROXY_URL') && PROXY_BYBRIDGE) {
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

			$card .= $getHelperButtonsFormat($formats);
		} else {
			$card .= '<span style="font-weight: bold;">Inactive</span>';
		}

		$card .= '</form>' . PHP_EOL;
	}

	$hasGlobalParameter = array_key_exists('global', $bridge->getParameters());

	if($hasGlobalParameter) {
		$globalParameters = $bridge->getParameters()['global'];
	}

	foreach($bridge->getParameters() as $parameterName => $parameter) {
		if(!is_numeric($parameterName) && $parameterName == 'global')
			continue;

		if($hasGlobalParameter)
			$parameter = array_merge($parameter, $globalParameters);

		if(!is_numeric($parameterName))
			$card .= '<h5>' . $parameterName . '</h5>' . PHP_EOL;

		$card .= $getFormHeader($bridgeName);
		$card .= $HTTPSWarning;

		foreach($parameter as $id => $inputEntry) {
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

			if(!isset($inputEntry['type']) || $inputEntry['type'] == 'text') {
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
			} elseif($inputEntry['type'] == 'number') {
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
			} else if($inputEntry['type'] == 'list') {
				$card .= '<select '
					. $additionalInfoString
					. ' id="'
					. $idArg
					. '" name="'
					. $id
					. '" >';

				foreach($inputEntry['values'] as $name => $value) {
					if(is_array($value)) {
						$card .= '<optgroup label="' . htmlentities($name) . '">';
						foreach($value as $subname => $subvalue) {
							if($inputEntry['defaultValue'] === $subname
								|| $inputEntry['defaultValue'] === $subvalue) {
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
							|| $inputEntry['defaultValue'] === $value) {
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
			} elseif($inputEntry['type'] == 'checkbox') {
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

		if($isActive) {
			if(defined('PROXY_URL') && PROXY_BYBRIDGE) {
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
			$card .= $getHelperButtonsFormat($formats);
		} else {
			$card .= '<span style="font-weight: bold;">Inactive</span>';
		}
		$card .= '</form>' . PHP_EOL;
	}

	$card .= '<label class="showless" for="showmore-' . $bridgeName . '">Show less</label>';
	$card .= '<p class="maintainer">' . $bridge->getMaintainer() . '</p>';
	$card .= '</section>';

	return $card;
}

function sanitize($textToSanitize,
$removedTags = array('script', 'iframe', 'input', 'form'),
$keptAttributes = array('title', 'href', 'src'),
$keptText = array()){
	$htmlContent = str_get_html($textToSanitize);

	foreach($htmlContent->find('*[!b38fd2b1fe7f4747d6b1c1254ccd055e]') as $element) {
		if(in_array($element->tag, $keptText)) {
			$element->outertext = $element->plaintext;
		} elseif(in_array($element->tag, $removedTags)) {
			$element->outertext = '';
		} else {
			foreach($element->getAllAttributes() as $attributeName => $attribute) {
				if(!in_array($attributeName, $keptAttributes))
					$element->removeAttribute($attributeName);
			}
		}
	}

	return $htmlContent;
}

function backgroundToImg($htmlContent) {

	$regex = '/background-image[ ]{0,}:[ ]{0,}url\([\'"]{0,}(.*?)[\'"]{0,}\)/';
	$htmlContent = str_get_html($htmlContent);

	foreach($htmlContent->find('*[!b38fd2b1fe7f4747d6b1c1254ccd055e]') as $element) {

		if(preg_match($regex, $element->style, $matches) > 0) {

			$element->outertext = '<img style="display:block;" src="' . $matches[1] . '" />';

		}

	}

	return $htmlContent;

}

function defaultLinkTo($content, $server){
	foreach($content->find('img') as $image) {
		if(strpos($image->src, 'http') === false
		&& strpos($image->src, '//') === false
		&& strpos($image->src, 'data:') === false)
			$image->src = $server . $image->src;
	}

	foreach($content->find('a') as $anchor) {
		if(strpos($anchor->href, 'http') === false
		&& strpos($anchor->href, '//') === false
		&& strpos($anchor->href, '#') !== 0
		&& strpos($anchor->href, '?') !== 0)
			$anchor->href = $server . $anchor->href;
	}

	return $content;
}
