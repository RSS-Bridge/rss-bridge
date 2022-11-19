<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

function render(string $template, array $context = []): string
{
    if ($template === 'base.html.php') {
        throw new \Exception('Do not render base.html.php into itself');
    }
    $context['system_message'] = Configuration::getConfig('system', 'message');
    $context['page'] = render_template($template, $context);
    return render_template('base.html.php', $context);
}

/**
 * Render template as absolute path or relative to templates folder.
 * Do not pass user input in $template
 */
function render_template(string $template, array $context = []): string
{
    if (isset($context['template'])) {
        throw new \Exception("Don't use `template` as a context key");
    }
    $templateFilepath = __DIR__ . '/../templates/' . $template;
    extract($context);
    ob_start();
    try {
        if (is_file($template)) {
            require $template;
        } elseif (is_file($templateFilepath)) {
            require $templateFilepath;
        } else {
            throw new \Exception(sprintf('Unable to find template `%s`', $template));
        }
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    return ob_get_clean();
}

/**
 * Escape for html context
 */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Explicitly don't escape
 */
function raw(string $s): string
{
    return $s;
}

function truncate(string $s, int $length = 150, $marker = '...'): string
{
    $s = trim($s);
    if (mb_strlen($s) <= $length) {
        return $s;
    }
    return mb_substr($s, 0, $length) . $marker;
}

/**
 * Removes unwanted tags from a given HTML text.
 *
 * @param string $html The HTML text to sanitize.
 * @param array $tags_to_remove A list of tags to remove from the DOM.
 * @param array $attributes_to_keep A list of attributes to keep on tags (other
 * attributes are removed).
 * @param array $text_to_keep A list of tags where the innertext replaces the tag
 * (i.e. `<p>Hello World!</p>` becomes `Hello World!`).
 * @return object A simplehtmldom object of the remaining contents.
 *
 * @todo Check if this implementation is still necessary, because simplehtmldom
 * already removes some of the tags (search for `remove_noise` in simple_html_dom.php).
 */
function sanitize(
    $html,
    $tags_to_remove = ['script', 'iframe', 'input', 'form'],
    $attributes_to_keep = ['title', 'href', 'src'],
    $text_to_keep = []
) {
    $htmlContent = str_get_html($html);

    foreach ($htmlContent->find('*') as $element) {
        if (in_array($element->tag, $text_to_keep)) {
            $element->outertext = $element->plaintext;
        } elseif (in_array($element->tag, $tags_to_remove)) {
            $element->outertext = '';
        } else {
            foreach ($element->getAllAttributes() as $attributeName => $attribute) {
                if (!in_array($attributeName, $attributes_to_keep)) {
                    $element->removeAttribute($attributeName);
                }
            }
        }
    }

    return $htmlContent;
}

function sanitize_html(string $html): string
{
    $html = str_replace('<script', '<&zwnj;script', $html); // Disable scripts, but leave them visible.
    $html = str_replace('<iframe', '<&zwnj;iframe', $html);
    $html = str_replace('<link', '<&zwnj;link', $html);
    // We leave alone object and embed so that videos can play in RSS readers.
    return $html;
}

/**
 * Replace background by image
 *
 * Replaces tags with styles of `backgroud-image` by `<img />` tags.
 *
 * For example:
 *
 * ```HTML
 * <html>
 *   <body style="background-image: url('bgimage.jpg');">
 *     <h1>Hello world!</h1>
 *   </body>
 * </html>
 * ```
 *
 * results in this output:
 *
 * ```HTML
 * <html>
 *   <img style="display:block;" src="bgimage.jpg" />
 * </html>
 * ```
 *
 * @param string $htmlContent The HTML content
 * @return string The HTML content with all ocurrences replaced
 */
function backgroundToImg($htmlContent)
{
    $regex = '/background-image[ ]{0,}:[ ]{0,}url\([\'"]{0,}(.*?)[\'"]{0,}\)/';
    $htmlContent = str_get_html($htmlContent);

    foreach ($htmlContent->find('*') as $element) {
        if (preg_match($regex, $element->style, $matches) > 0) {
            $element->outertext = '<img style="display:block;" src="' . $matches[1] . '" />';
        }
    }

    return $htmlContent;
}

/**
 * Convert relative links in HTML into absolute links
 *
 * This function is based on `php-urljoin`.
 *
 * @link https://github.com/plaidfluff/php-urljoin php-urljoin
 *
 * @param string|object $dom The HTML content. Supports HTML objects or string objects
 * @param string $url Fully qualified URL to the page containing relative links
 * @return string|object Content with fixed URLs.
 */
function defaultLinkTo($dom, $url)
{
    if ($dom === '') {
        return $url;
    }

    $string_convert = false;
    if (is_string($dom)) {
        $string_convert = true;
        $dom = str_get_html($dom);
    }

    foreach ($dom->find('img') as $image) {
        $image->src = urljoin($url, $image->src);
    }

    foreach ($dom->find('a') as $anchor) {
        $anchor->href = urljoin($url, $anchor->href);
    }

    if ($string_convert) {
        $dom = $dom->outertext;
    }

    return $dom;
}

/**
 * Convert lazy-loading images and frames (video embeds) into static elements
 *
 * This function looks for lazy-loading attributes such as 'data-src' and converts
 * them back to regular ones such as 'src', making them loadable in RSS readers.
 * It also converts <picture> elements to plain <img> elements.
 *
 * @param string|object $content The HTML content. Supports HTML objects or string objects
 * @return string|object Content with fixed image/frame URLs (same type as input).
 */
function convertLazyLoading($dom)
{
    $string_convert = false;
    if (is_string($dom)) {
        $string_convert = true;
        $dom = str_get_html($dom);
    }

    // Process standalone images, embeds and picture sources
    foreach ($dom->find('img, iframe, source') as $img) {
        if (!empty($img->getAttribute('data-src'))) {
            $img->src = $img->getAttribute('data-src');
        } elseif (!empty($img->getAttribute('data-srcset'))) {
            $img->src = explode(' ', $img->getAttribute('data-srcset'))[0];
        } elseif (!empty($img->getAttribute('data-lazy-src'))) {
            $img->src = $img->getAttribute('data-lazy-src');
        } elseif (!empty($img->getAttribute('srcset'))) {
            $img->src = explode(' ', $img->getAttribute('srcset'))[0];
        } else {
            continue; // Proceed to next element without removing attributes
        }
        foreach (['loading', 'decoding', 'srcset', 'data-src', 'data-srcset'] as $attr) {
            if ($img->hasAttribute($attr)) {
                $img->removeAttribute($attr);
            }
        }
    }

    // Convert complex HTML5 pictures to plain, standalone images
    // <img> and <source> tags already have their "src" attribute set at this point,
    // so we replace the whole <picture> with a standalone <img> from within the <picture>
    foreach ($dom->find('picture') as $picture) {
        $img = $picture->find('img, source', 0);
        if (!empty($img)) {
            if ($img->tag == 'source') {
                $img->tag = 'img';
            }
            // Adding/removing node would change its position inside the parent element,
            // So instead we rewrite the node in-place though the outertext attribute
            $picture->outertext = $img->outertext;
        }
    }

    // If the expected return type is object, reload the DOM to make sure
    // all $picture->outertext rewritten above are converted back to objects
    $dom = $dom->outertext;
    if (!$string_convert) {
        $dom = str_get_html($dom);
    }

    return $dom;
}

/**
 * Extract the first part of a string matching the specified start and end delimiters
 *
 * @param string $string Input string, e.g. `<div>Post author: John Doe</div>`
 * @param string $start Start delimiter, e.g. `author: `
 * @param string $end End delimiter, e.g. `<`
 * @return string|bool Extracted string, e.g. `John Doe`, or false if the
 * delimiters were not found.
 */
function extractFromDelimiters($string, $start, $end)
{
    if (strpos($string, $start) !== false) {
        $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
        $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
        return $section_retrieved;
    }
    return false;
}

/**
 * Remove one or more part(s) of a string using a start and end delmiters
 *
 * @param string $string Input string, e.g. `foo<script>superscript()</script>bar`
 * @param string $start Start delimiter, e.g. `<script>`
 * @param string $end End delimiter, e.g. `</script>`
 * @return string Cleaned string, e.g. `foobar`
 */
function stripWithDelimiters($string, $start, $end)
{
    while (strpos($string, $start) !== false) {
        $section_to_remove = substr($string, strpos($string, $start));
        $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
        $string = str_replace($section_to_remove, '', $string);
    }
    return $string;
}

/**
 * Remove HTML sections containing one or more sections using the same HTML tag
 *
 * @param string $string Input string, e.g. `foo<div class="ads"><div>ads</div>ads</div>bar`
 * @param string $tag_name Name of the HTML tag, e.g. `div`
 * @param string $tag_start Start of the HTML tag to remove, e.g. `<div class="ads">`
 * @return string Cleaned String, e.g. `foobar`
 *
 * This function works by locating the desired tag start, then finding the appropriate
 * end by counting opening and ending tags until the amount of open tags reaches zero:
 *
 * ```
 * Amount of open tags:
 *         1          2       1        0
 * |---------------||---|   |----|   |----|
 * <div class="ads"><div>ads</div>ads</div>bar
 * | <-------- Section to remove -------> |
 * ```
 */
function stripRecursiveHTMLSection($string, $tag_name, $tag_start)
{
    $open_tag = '<' . $tag_name;
    $close_tag = '</' . $tag_name . '>';
    $close_tag_length = strlen($close_tag);

    // Make sure the provided $tag_start argument matches the provided $tag_name argument
    if (strpos($tag_start, $open_tag) === 0) {
        // While tag_start is present, there is at least one remaining section to remove
        while (strpos($string, $tag_start) !== false) {
            // In order to locate the end of the section, we attempt each closing tag until we find the right one
            // We know we found the right one when the amount of "<tag" is the same as amount of "</tag"
            // When the attempted "</tag" is not the correct one, we increase $search_offset to skip it
            // and retry unless $max_recursion is reached (prevents infinite loop on malformed HTML)
            $max_recursion = 100;
            $section_to_remove = null;
            $section_start = strpos($string, $tag_start);
            $search_offset = $section_start;
            do {
                $max_recursion--;
                // Move on to the next occurrence of "</tag"
                $section_end = strpos($string, $close_tag, $search_offset);
                $search_offset = $section_end + $close_tag_length;
                // If the next "</tag" is the correct one, then this is the section we must remove:
                $section_to_remove = substr($string, $section_start, $section_end - $section_start + $close_tag_length);
                // Count amount of "<tag" and "</tag" in the section to remove
                $open_tag_count = substr_count($section_to_remove, $open_tag);
                $close_tag_count = substr_count($section_to_remove, $close_tag);
            } while ($open_tag_count > $close_tag_count && $max_recursion > 0);
            // We exited the loop, let's remove the section
            $string = str_replace($section_to_remove, '', $string);
        }
    }
    return $string;
}

/**
 * Convert Markdown into HTML with Parsedown.
 *
 * @link https://parsedown.org/ Parsedown
 *
 * @param string $string Input string in Markdown format
 * @return string output string in HTML format
 */
function markdownToHtml($string)
{
    $Parsedown = new Parsedown();
    return $Parsedown->text($string);
}
