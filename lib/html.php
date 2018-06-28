<?php
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
