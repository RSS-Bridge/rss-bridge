<?php

class XPathBridge extends XPathAbstract
{
    const NAME = 'XPathBridge';
    const URI = 'https://github.com/rss-bridge/rss-bridge';
    const DESCRIPTION
        = 'Parse any webpage using <a href="https://devhints.io/xpath" target="_blank">XPath expressions</a>';
    const MAINTAINER = 'Niehztog';
    const PARAMETERS = [
        '' => [

            'url' => [
                'name' => 'Enter web page URL',
                'title' => <<<"EOL"
You can specify any website URL which serves data suited for display in RSS feeds
(for example a news blog).
EOL, 'type' => 'text',
                'exampleValue' => 'https://news.blizzard.com/en-en',
                'defaultValue' => 'https://news.blizzard.com/en-en',
                'required' => true
            ],

            'item' => [
                'name' => 'Item selector',
                'title' => <<<"EOL"
Enter an XPath expression matching a list of dom nodes, each node containing one
feed article item in total (usually a surrounding &lt;div&gt; or &lt;span&gt; tag). This will
be the context nodes for all of the following expressions. This expression usually
starts with a single forward slash.
EOL, 'type' => 'text',
                'exampleValue' => '/html/body/div/div[4]/div[2]/div[2]/div/div/section/ol/li/article',
                'defaultValue' => '/html/body/div/div[4]/div[2]/div[2]/div/div/section/ol/li/article',
                'required' => true
            ],

            'title' => [
                'name' => 'Item title selector',
                'title' => <<<"EOL"
This expression should match a node contained within each article item node
containing the article headline. It should start with a dot followed by two
forward slashes, referring to any descendant nodes of the article item node.
EOL, 'type' => 'text',
                'exampleValue' => './/div/div[2]/h2',
                'defaultValue' => './/div/div[2]/h2',
                'required' => true
            ],

            'content' => [
                'name' => 'Item description selector',
                'title' => <<<"EOL"
This expression should match a node contained within each article item node
containing the article content or description. It should start with a dot
followed by two forward slashes, referring to any descendant nodes of the
article item node.
EOL, 'type' => 'text',
                'exampleValue' => './/div[@class="ArticleListItem-description"]/div[@class="h6"]',
                'defaultValue' => './/div[@class="ArticleListItem-description"]/div[@class="h6"]',
                'required' => false
            ],

            'raw_content' => [
                'name' => 'Use raw item description',
                'title' => <<<"EOL"
                Whether to use the raw item description or to replace certain characters with
                special significance in HTML by HTML entities (using the PHP function htmlspecialchars).
                EOL,
                'type' => 'checkbox',
                'defaultValue' => false,
                'required' => false
            ],

            'uri' => [
                'name' => 'Item URL selector',
                'title' => <<<"EOL"
This expression should match a node's attribute containing the article URL
(usually the href attribute of an &lt;a&gt; tag). It should start with a dot
followed by two forward slashes, referring to any descendant nodes of
the article item node. Attributes can be selected by prepending an @ char
before the attributes name.
EOL, 'type' => 'text',
                'exampleValue' => './/a[@class="ArticleLink ArticleLink"]/@href',
                'defaultValue' => './/a[@class="ArticleLink ArticleLink"]/@href',
                'required' => false
            ],

            'author' => [
                'name' => 'Item author selector',
                'title' => <<<"EOL"
This expression should match a node contained within each article item
node containing the article author's name. It should start with a dot
followed by two forward slashes, referring to any descendant nodes of
the article item node.
EOL, 'type' => 'text',
                'required' => false
            ],

            'timestamp' => [
                'name' => 'Item date selector',
                'title' => <<<"EOL"
This expression should match a node or node's attribute containing the
article timestamp or date (parsable by PHP's strtotime function). It
should start with a dot followed by two forward slashes, referring to
any descendant nodes of the article item node. Attributes can be
selected by prepending an @ char before the attributes name.
EOL, 'type' => 'text',
                'exampleValue' => './/time[@class="ArticleListItem-footerTimestamp"]/@timestamp',
                'defaultValue' => './/time[@class="ArticleListItem-footerTimestamp"]/@timestamp',
                'required' => false
            ],

            'enclosures' => [
                'name' => 'Item image selector',
                'title' => <<<"EOL"
This expression should match a node's attribute containing an article
image URL (usually the src attribute of an &lt;img&gt; tag or a style
attribute). It should start with a dot followed by two forward slashes,
referring to any descendant nodes of the article item node. Attributes
can be selected by prepending an @ char before the attributes name.
EOL, 'type' => 'text',
                'exampleValue' => './/div[@class="ArticleListItem-image"]/@style',
                'defaultValue' => './/div[@class="ArticleListItem-image"]/@style',
                'required' => false
            ],

            'categories' => [
                'name' => 'Item category selector',
                'title' => <<<"EOL"
This expression should match a node or node's attribute contained
within each article item node containing the article category. This
could be inside &lt;div&gt; or &lt;span&gt; tags or sometimes be hidden
in a data attribute. It should start with a dot followed by two
forward slashes, referring to any descendant nodes of the article
item node. Attributes can be selected by prepending an @ char
before the attributes name.
EOL, 'type' => 'text',
                'exampleValue' => './/div[@class="ArticleListItem-label"]',
                'defaultValue' => './/div[@class="ArticleListItem-label"]',
                'required' => false
            ],

            'fix_encoding' => [
                'name' => 'Fix encoding',
                'title' => <<<"EOL"
Check this to fix feed encoding by invoking PHP's utf8_decode
function on all extracted texts. Try this in case you see "broken" or
"weird" characters in your feed where you'd normally expect umlauts
or any other non-ascii characters.
EOL, 'type' => 'checkbox',
                'required' => false
            ],

        ]
    ];

    /**
     * Source Web page URL (should provide either HTML or XML content)
     * @return string
     */
    protected function getSourceUrl()
    {
        return $this->encodeUri($this->getInput('url'));
    }

    /**
     * XPath expression for extracting the feed items from the source page
     * @return string
     */
    protected function getExpressionItem()
    {
        return urldecode($this->getInput('item'));
    }

    /**
     * XPath expression for extracting an item title from the item context
     * @return string
     */
    protected function getExpressionItemTitle()
    {
        return urldecode($this->getInput('title'));
    }

    /**
     * XPath expression for extracting an item's content from the item context
     * @return string
     */
    protected function getExpressionItemContent()
    {
        return urldecode($this->getInput('content'));
    }

    /**
     * Use raw item content
     * @return bool
     */
    protected function getSettingUseRawItemContent(): bool
    {
        return $this->getInput('raw_content');
    }

    /**
     * XPath expression for extracting an item link from the item context
     * @return string
     */
    protected function getExpressionItemUri()
    {
        return urldecode($this->getInput('uri'));
    }

    /**
     * XPath expression for extracting an item author from the item context
     * @return string
     */
    protected function getExpressionItemAuthor()
    {
        return urldecode($this->getInput('author'));
    }

    /**
     * XPath expression for extracting an item timestamp from the item context
     * @return string
     */
    protected function getExpressionItemTimestamp()
    {
        return urldecode($this->getInput('timestamp'));
    }

    /**
     * XPath expression for extracting item enclosures (media content like
     * images or movies) from the item context
     * @return string
     */
    protected function getExpressionItemEnclosures()
    {
        return urldecode($this->getInput('enclosures'));
    }

    /**
     * XPath expression for extracting an item category from the item context
     * @return string
     */
    protected function getExpressionItemCategories()
    {
        return urldecode($this->getInput('categories'));
    }

    /**
     * Fix encoding
     * @return bool
     */
    protected function getSettingFixEncoding(): bool
    {
        return $this->getInput('fix_encoding');
    }

    /**
     * Fixes URL encoding issues in input URL's
     * @param $uri
     * @return string|string[]
     */
    private function encodeUri($uri)
    {
        if (
            strpos($uri, 'https%3A%2F%2F') === 0
            || strpos($uri, 'http%3A%2F%2F') === 0
        ) {
            $uri = urldecode($uri);
        }

        $uri = str_replace('|', '%7C', $uri);

        return $uri;
    }
}
