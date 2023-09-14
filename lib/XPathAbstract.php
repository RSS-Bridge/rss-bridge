<?php

/**
 * An alternative abstract class for bridges utilizing XPath expressions
 *
 * This class is meant as an alternative base class for bridge implementations.
 * It offers preliminary functionality for generating feeds based on XPath
 * expressions.
 * As a minimum, extending classes should define XPath expressions pointing
 * to the feed items contents in the class constants below. In case there is
 * more manual fine tuning required, it offers a bunch of methods which can
 * be overridden, for example in order to specify formatting of field values
 * or more flexible definition of dynamic XPath expressions.
 *
 * This class extends {@see BridgeAbstract}, which means it incorporates and
 * extends all of its functionality.
 **/
abstract class XPathAbstract extends BridgeAbstract
{
    /**
     * Source Web page URL (should provide either HTML or XML content)
     * You can specify any website URL which serves data suited for display in RSS feeds
     * (for example a news blog).
     *
     * Use {@see XPathAbstract::getSourceUrl()} to read this parameter
     */
    const FEED_SOURCE_URL = '';

    /**
     * XPath expression for extracting the feed title from the source page.
     * If this is left blank or does not provide any data {@see BridgeAbstract::getName()}
     * is used instead as the feed's title.
     *
     * Use {@see XPathAbstract::getExpressionTitle()} to read this parameter
     */
    const XPATH_EXPRESSION_FEED_TITLE = './/title';

    /**
     * XPath expression for extracting the feed favicon URL from the source page.
     * If this is left blank or does not provide any data {@see BridgeAbstract::getIcon()}
     * is used instead as the feed's favicon URL.
     *
     * Use {@see XPathAbstract::getExpressionIcon()} to read this parameter
     */
    const XPATH_EXPRESSION_FEED_ICON = './/link[@rel="icon"]/@href';

    /**
     * XPath expression for extracting the feed items from the source page
     * Enter an XPath expression matching a list of dom nodes, each node containing one
     * feed article item in total (usually a surrounding <div> or <span> tag). This will
     * be the context nodes for all of the following expressions. This expression usually
     * starts with a single forward slash.
     *
     * Use {@see XPathAbstract::getExpressionItem()} to read this parameter
     */
    const XPATH_EXPRESSION_ITEM = '';

    /**
     * XPath expression for extracting an item title from the item context
     * This expression should match a node contained within each article item node
     * containing the article headline. It should start with a dot followed by two
     * forward slashes, referring to any descendant nodes of the article item node.
     *
     * Use {@see XPathAbstract::getExpressionItemTitle()} to read this parameter
     */
    const XPATH_EXPRESSION_ITEM_TITLE = '';

    /**
     * XPath expression for extracting an item's content from the item context
     * This expression should match a node contained within each article item node
     * containing the article content or description. It should start with a dot
     * followed by two forward slashes, referring to any descendant nodes of the
     * article item node.
     *
     * Use {@see XPathAbstract::getExpressionItemContent()} to read this parameter
     */
    const XPATH_EXPRESSION_ITEM_CONTENT = '';

    /**
     * Use raw item content
     * Whether to use the raw item content or to replace certain characters with
     * special significance in HTML by HTML entities (using the PHP function htmlspecialchars).
     *
     * Use {@see XPathAbstract::getSettingUseRawItemContent()} to read this parameter
     */
    const SETTING_USE_RAW_ITEM_CONTENT = false;

    /**
     * XPath expression for extracting an item link from the item context
     * This expression should match a node's attribute containing the article URL
     * (usually the href attribute of an <a> tag). It should start with a dot
     * followed by two forward slashes, referring to any descendant nodes of
     * the article item node. Attributes can be selected by prepending an @ char
     * before the attributes name.
     *
     * Use {@see XPathAbstract::getExpressionItemUri()} to read this parameter
     */
    const XPATH_EXPRESSION_ITEM_URI = '';

    /**
     * XPath expression for extracting an item author from the item context
     * This expression should match a node contained within each article item
     * node containing the article author's name. It should start with a dot
     * followed by two forward slashes, referring to any descendant nodes of
     * the article item node.
     *
     * Use {@see XPathAbstract::getExpressionItemAuthor()} to read this parameter
     */
    const XPATH_EXPRESSION_ITEM_AUTHOR = '';

    /**
     * XPath expression for extracting an item timestamp from the item context
     * This expression should match a node or node's attribute containing the
     * article timestamp or date (parsable by PHP's strtotime function). It
     * should start with a dot followed by two forward slashes, referring to
     * any descendant nodes of the article item node. Attributes can be
     * selected by prepending an @ char before the attributes name.
     *
     * Use {@see XPathAbstract::getExpressionItemTimestamp()} to read this parameter
     */
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = '';

    /**
     * XPath expression for extracting item enclosures (media content like
     * images or movies) from the item context
     * This expression should match a node's attribute containing an article
     * image URL (usually the src attribute of an <img> tag or a style
     * attribute). It should start with a dot followed by two forward slashes,
     * referring to any descendant nodes of the article item node. Attributes
     * can be selected by prepending an @ char before the attributes name.
     *
     * Use {@see XPathAbstract::getExpressionItemEnclosures()} to read this parameter
     */
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = '';

    /**
     * XPath expression for extracting an item category from the item context
     * This expression should match a node or node's attribute contained
     * within each article item node containing the article category. This
     * could be inside <div> or <span> tags or sometimes be hidden
     * in a data attribute. It should start with a dot followed by two
     * forward slashes, referring to any descendant nodes of the article
     * item node. Attributes can be selected by prepending an @ char
     * before the attributes name.
     *
     * Use {@see XPathAbstract::getExpressionItemCategories()} to read this parameter
     */
    const XPATH_EXPRESSION_ITEM_CATEGORIES = '';

    /**
     * Fix encoding
     * Set this to true for fixing feed encoding by invoking PHP's utf8_decode
     * function on all extracted texts. Try this in case you see "broken" or
     * "weird" characters in your feed where you'd normally expect umlauts
     * or any other non-ascii characters.
     *
     * Use {@see XPathAbstract::getSettingFixEncoding()} to read this parameter
     */
    const SETTING_FIX_ENCODING = false;

    /**
     * Internal storage for resulting feed name, automatically detected
     * @var string
     */
    private $feedName;

    /**
     * Internal storage for resulting feed name, automatically detected
     * @var string
     */
    private $feedUri;

    /**
     * Internal storage for resulting feed favicon, automatically detected
     * @var string
     */
    private $feedIcon;

    public function getName()
    {
        return $this->feedName ?: parent::getName();
    }

    public function getURI()
    {
        return $this->feedUri ?: parent::getURI();
    }

    public function getIcon()
    {
        return $this->feedIcon ?: parent::getIcon();
    }

    /**
     * Source Web page URL (should provide either HTML or XML content)
     * @return string
     */
    protected function getSourceUrl()
    {
        return static::FEED_SOURCE_URL;
    }

    /**
     * XPath expression for extracting the feed title from the source page
     * @return string
     */
    protected function getExpressionTitle()
    {
        return static::XPATH_EXPRESSION_FEED_TITLE;
    }

    /**
     * XPath expression for extracting the feed favicon from the source page
     * @return string
     */
    protected function getExpressionIcon()
    {
        return static::XPATH_EXPRESSION_FEED_ICON;
    }

    /**
     * XPath expression for extracting the feed items from the source page
     * @return string
     */
    protected function getExpressionItem()
    {
        return static::XPATH_EXPRESSION_ITEM;
    }

    /**
     * XPath expression for extracting an item title from the item context
     * @return string
     */
    protected function getExpressionItemTitle()
    {
        return static::XPATH_EXPRESSION_ITEM_TITLE;
    }

    /**
     * XPath expression for extracting an item's content from the item context
     * @return string
     */
    protected function getExpressionItemContent()
    {
        return static::XPATH_EXPRESSION_ITEM_CONTENT;
    }

    /**
     * Use raw item content
     * @return bool
     */
    protected function getSettingUseRawItemContent(): bool
    {
        return static::SETTING_USE_RAW_ITEM_CONTENT;
    }

    /**
     * XPath expression for extracting an item link from the item context
     * @return string
     */
    protected function getExpressionItemUri()
    {
        return static::XPATH_EXPRESSION_ITEM_URI;
    }

    /**
     * XPath expression for extracting an item author from the item context
     * @return string
     */
    protected function getExpressionItemAuthor()
    {
        return static::XPATH_EXPRESSION_ITEM_AUTHOR;
    }

    /**
     * XPath expression for extracting an item timestamp from the item context
     * @return string
     */
    protected function getExpressionItemTimestamp()
    {
        return static::XPATH_EXPRESSION_ITEM_TIMESTAMP;
    }

    /**
     * XPath expression for extracting item enclosures (media content like
     * images or movies) from the item context
     * @return string
     */
    protected function getExpressionItemEnclosures()
    {
        return static::XPATH_EXPRESSION_ITEM_ENCLOSURES;
    }

    /**
     * XPath expression for extracting an item category from the item context
     * @return string
     */
    protected function getExpressionItemCategories()
    {
        return static::XPATH_EXPRESSION_ITEM_CATEGORIES;
    }

    /**
     * Fix encoding
     * @return bool
     */
    protected function getSettingFixEncoding(): bool
    {
        return static::SETTING_FIX_ENCODING;
    }

    /**
     * Internal helper method for quickly accessing all the user defined constants
     * in derived classes
     *
     * @param $name
     * @return bool|string
     */
    private function getParam($name)
    {
        switch ($name) {
            case 'url':
                return $this->getSourceUrl();
            case 'feed_title':
                return $this->getExpressionTitle();
            case 'feed_icon':
                return $this->getExpressionIcon();
            case 'item':
                return $this->getExpressionItem();
            case 'title':
                return $this->getExpressionItemTitle();
            case 'content':
                return $this->getExpressionItemContent();
            case 'raw_content':
                return $this->getSettingUseRawItemContent();
            case 'uri':
                return $this->getExpressionItemUri();
            case 'author':
                return $this->getExpressionItemAuthor();
            case 'timestamp':
                return $this->getExpressionItemTimestamp();
            case 'enclosures':
                return $this->getExpressionItemEnclosures();
            case 'categories':
                return $this->getExpressionItemCategories();
            case 'fix_encoding':
                return $this->getSettingFixEncoding();
        }
    }

    /**
     * Should provide the source website HTML content
     * can be easily overwritten for example if special headers or auth infos are required
     * @return string
     */
    protected function provideWebsiteContent()
    {
        return getContents($this->feedUri);
    }

    /**
     * Should provide the feeds title
     *
     * @param \DOMXPath $xpath
     * @return string
     */
    protected function provideFeedTitle(\DOMXPath $xpath)
    {
        $title = $xpath->query($this->getParam('feed_title'));
        if (count($title) === 1) {
            return $this->fixEncoding($this->getItemValueOrNodeValue($title));
        }
    }

    /**
     * Should provide the URL of the feed's favicon
     *
     * @param \DOMXPath $xpath
     * @return string
     */
    protected function provideFeedIcon(\DOMXPath $xpath)
    {
        $icon = $xpath->query($this->getParam('feed_icon'));
        if (count($icon) === 1) {
            return $this->cleanMediaUrl($this->getItemValueOrNodeValue($icon));
        }
    }

    /**
     * Should provide the feed's items.
     *
     * @param \DOMXPath $xpath
     * @return \DOMNodeList
     */
    protected function provideFeedItems(\DOMXPath $xpath)
    {
        return @$xpath->query($this->getParam('item'));
    }

    public function collectData()
    {
        $this->feedUri = $this->getParam('url');

        $webPageHtml = new \DOMDocument();
        libxml_use_internal_errors(true);
        $webPageHtml->loadHTML($this->provideWebsiteContent());
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        // fix relative links
        defaultLinkTo($webPageHtml, $webPageHtml->baseURI ?? $this->feedUri);

        $xpath = new \DOMXPath($webPageHtml);

        $this->feedName = $this->provideFeedTitle($xpath);
        $this->feedIcon = $this->provideFeedIcon($xpath);

        $entries = $this->provideFeedItems($xpath);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            $item = new FeedItem();
            foreach (['title', 'content', 'uri', 'author', 'timestamp', 'enclosures', 'categories'] as $param) {
                $expression = $this->getParam($param);
                if ('' === $expression) {
                    continue;
                }

                //can be a string or DOMNodeList, depending on the expression result
                $typedResult = @$xpath->evaluate($expression, $entry);
                if (
                    $typedResult === false || ($typedResult instanceof \DOMNodeList && count($typedResult) === 0)
                    || (is_string($typedResult) && strlen(trim($typedResult)) === 0)
                ) {
                    continue;
                }

                $isContent = $param === 'content';
                $value = $this->getItemValueOrNodeValue($typedResult, $isContent, $isContent && !$this->getSettingUseRawItemContent());
                $item->__set($param, $this->formatParamValue($param, $value));
            }

            $itemId = $this->generateItemId($item);
            if (null !== $itemId) {
                $item->setUid($itemId);
            }

            $this->items[] = $item;
        }
    }

    /**
     * @param $param
     * @param $value
     * @return string|array
     */
    protected function formatParamValue($param, $value)
    {
        $value = $this->fixEncoding($value);
        switch ($param) {
            case 'title':
                return $this->formatItemTitle($value);
            case 'content':
                return $this->formatItemContent($value);
            case 'uri':
                return $this->formatItemUri($value);
            case 'author':
                return $this->formatItemAuthor($value);
            case 'timestamp':
                return $this->formatItemTimestamp($value);
            case 'enclosures':
                return $this->formatItemEnclosures($value);
            case 'categories':
                return $this->formatItemCategories($value);
        }
        return $value;
    }

    /**
     * Formats the title of a feed item. Takes extracted raw title and returns it formatted
     * as string.
     * Can be easily overwritten for in case the value needs to be transformed into something
     * else.
     * @param string $value
     * @return string
     */
    protected function formatItemTitle($value)
    {
        return $value;
    }

    /**
     * Formats the timestamp of a feed item. Takes extracted raw timestamp and returns unix
     * timestamp as integer.
     * Can be easily overwritten for example if a special format has to be expected on the
     * source website.
     * @param string $value
     * @return string
     */
    protected function formatItemContent($value)
    {
        return $value;
    }

    /**
     * Formats the URI of a feed item. Takes extracted raw URI and returns it formatted
     * as string.
     * Can be easily overwritten for in case the value needs to be transformed into something
     * else.
     * @param string $value
     * @return string
     */
    protected function formatItemUri($value)
    {
        if (strlen($value) === 0) {
            return '';
        }
        if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
            return $value;
        }

        return urljoin($this->feedUri, $value);
    }

    /**
     * Formats the author of a feed item. Takes extracted raw author and returns it formatted
     * as string.
     * Can be easily overwritten for in case the value needs to be transformed into something
     * else.
     * @param string $value
     * @return string
     */
    protected function formatItemAuthor($value)
    {
        return $value;
    }

    /**
     * Formats the timestamp of a feed item. Takes extracted raw timestamp and returns unix
     * timestamp as integer.
     * Can be easily overwritten for example if a special format has to be expected on the
     * source website.
     * @param string $value
     * @return false|int
     */
    protected function formatItemTimestamp($value)
    {
        return strtotime($value);
    }

    /**
     * Formats the enclosures of a feed item. Takes extracted raw enclosures and returns them
     * formatted as array.
     * Can be easily overwritten for in case the values need to be transformed into something
     * else.
     * @param string $value
     * @return array
     */
    protected function formatItemEnclosures($value)
    {
        return [$this->cleanMediaUrl($value)];
    }

    /**
     * Formats the categories of a feed item. Takes extracted raw categories and returns them
     * formatted as array.
     * Can be easily overwritten for in case the values need to be transformed into something
     * else.
     * @param string $value
     * @return array
     */
    protected function formatItemCategories($value)
    {
        return [$value];
    }

    /**
     * @param $mediaUrl
     * @return string|void
     */
    protected function cleanMediaUrl($mediaUrl)
    {
        $pattern = '~(?:http(?:s)?:)?[\/a-zA-Z0-9\-=_,\.\%]+\.(?:jpg|gif|png|jpeg|ico|mp3|webp){1}~i';
        $result = preg_match($pattern, $mediaUrl, $matches);
        if (1 !== $result) {
            return;
        }
        return urljoin($this->feedUri, $matches[0]);
    }

    /**
     * @param $typedResult
     * @return string
     */
    protected function getItemValueOrNodeValue($typedResult, $returnXML = false, $escapeHtml = false)
    {
        if ($typedResult instanceof \DOMNodeList) {
            $item = $typedResult->item(0);
            if ($item instanceof \DOMElement) {
                // Don't escape XML
                if ($returnXML) {
                    return ($item->ownerDocument ?? $item)->saveXML($item);
                }
                $text = $item->nodeValue;
            } elseif ($item instanceof \DOMAttr) {
                $text = $item->value;
            } elseif ($item instanceof \DOMText) {
                $text = $item->wholeText;
            }
        } elseif (is_string($typedResult) && strlen($typedResult) > 0) {
            $text = $typedResult;
        } else {
            throw new \Exception('Unknown type of XPath expression result.');
        }

        $text = trim($text);

        if ($escapeHtml) {
            return htmlspecialchars($text);
        }
        return $text;
    }

    /**
     * Fixes feed encoding by invoking PHP's utf8_decode function on extracted texts.
     * Useful in case of "broken" or "weird" characters in the feed where you'd normally
     * expect umlauts.
     *
     * @param $input
     * @return string
     */
    protected function fixEncoding($input)
    {
        return $this->getParam('fix_encoding') ? utf8_decode($input) : $input;
    }

    /**
     * Allows overriding default mechanism determining items Uid's
     *
     * @param FeedItem $item
     * @return string|null
     */
    protected function generateItemId(FeedItem $item)
    {
        return null;
    }
}
