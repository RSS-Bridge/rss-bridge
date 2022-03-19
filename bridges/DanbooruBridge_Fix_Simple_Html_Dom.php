<?php

/**
 * This class is a monkey patch to 'extend' simplehtmldom to recognize <source>
 * tags (HTML5) as self closing tag. This patch should be removed once
 * simplehtmldom was fixed. This seems to be a issue with more tags:
 * https://sourceforge.net/p/simplehtmldom/bugs/83/
 *
 * The tag itself is valid according to Mozilla:
 *
 * The HTML <picture> element serves as a container for zero or more <source>
 * elements and one <img> element to provide versions of an image for different
 * display device scenarios. The browser will consider each of the child <source>
 * elements and select one corresponding to the best match found; if no matches
 * are found among the <source> elements, the file specified by the <img>
 * element's src attribute is selected. The selected image is then presented in
 * the space occupied by the <img> element.
 *
 * -- https://developer.mozilla.org/en-US/docs/Web/HTML/Element/picture
 *
 * Notice: This class uses parts of the original simplehtmldom, adjusted to pass
 * the guidelines of RSS-Bridge (formatting)
 */
final class Fix_Simple_Html_Dom extends simple_html_dom {

	/* copy from simple_html_dom, added 'source' at the end */
	protected $self_closing_tags = array(
		'img' => 1,
		'br' => 1,
		'input' => 1,
		'meta' => 1,
		'link' => 1,
		'hr' => 1,
		'base' => 1,
		'embed' => 1,
		'spacer' => 1,
		'source' => 1
	);

	/* copy from simplehtmldom, changed 'simple_html_dom' to 'Fix_Simple_Html_Dom' */
	public static function str_get_html($str,
										$lowercase = true,
										$forceTagsClosed = true,
										$target_charset = DEFAULT_TARGET_CHARSET,
										$stripRN = true,
										$defaultBRText = DEFAULT_BR_TEXT,
										$defaultSpanText = DEFAULT_SPAN_TEXT)
	{
		$dom = new Fix_Simple_Html_Dom(null,
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
}
