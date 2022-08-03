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
    $context['page'] = render_template($template, $context);
    return render_template('base.html.php', $context);
}

function render_template(string $template, array $context = []): string
{
    if (isset($context['template'])) {
        throw new \Exception("Don't use `template` as a context key");
    }
    extract($context);
    ob_start();
    try {
        require __DIR__ . '/../templates/' . $template;
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
 * @param string|object $content The HTML content. Supports HTML objects or string objects
 * @param string $server Fully qualified URL to the page containing relative links
 * @return object Content with fixed URLs.
 */
function defaultLinkTo($content, $server)
{
    $string_convert = false;
    if (is_string($content)) {
        $string_convert = true;
        $content = str_get_html($content);
    }

    foreach ($content->find('img') as $image) {
        $image->src = urljoin($server, $image->src);
    }

    foreach ($content->find('a') as $anchor) {
        $anchor->href = urljoin($server, $anchor->href);
    }

    if ($string_convert) {
        $content = $content->outertext;
    }

    return $content;
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
 * @todo This function needs more documentation to make it maintainable.
 */
function stripRecursiveHTMLSection($string, $tag_name, $tag_start)
{
    $open_tag = '<' . $tag_name;
    $close_tag = '</' . $tag_name . '>';
    $close_tag_length = strlen($close_tag);
    if (strpos($tag_start, $open_tag) === 0) {
        while (strpos($string, $tag_start) !== false) {
            $max_recursion = 100;
            $section_to_remove = null;
            $section_start = strpos($string, $tag_start);
            $search_offset = $section_start;
            do {
                $max_recursion--;
                $section_end = strpos($string, $close_tag, $search_offset);
                $search_offset = $section_end + $close_tag_length;
                $section_to_remove = substr($string, $section_start, $section_end - $section_start + $close_tag_length);
                $open_tag_count = substr_count($section_to_remove, $open_tag);
                $close_tag_count = substr_count($section_to_remove, $close_tag);
            } while ($open_tag_count > $close_tag_count && $max_recursion > 0);
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
