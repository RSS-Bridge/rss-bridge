<?php

/**
 * Expands an existing feed
 */
abstract class FeedExpander extends BridgeAbstract
{
    const FEED_TYPE_RSS_1_0 = 'RSS_1_0';
    const FEED_TYPE_RSS_2_0 = 'RSS_2_0';
    const FEED_TYPE_ATOM_1_0 = 'ATOM_1_0';

    private string $feedType;
    private FeedParser $feedParser;
    private array $parsedFeed;

    public function __construct(CacheInterface $cache, Logger $logger)
    {
        parent::__construct($cache, $logger);
        $this->feedParser = new FeedParser();
    }

    public function collectExpandableDatas(string $url, $maxItems = -1)
    {
        if (!$url) {
            throw new \Exception('There is no $url for this RSS expander');
        }
        if ($maxItems === -1) {
            $maxItems = 999;
        }
        $accept = [MrssFormat::MIME_TYPE, AtomFormat::MIME_TYPE, '*/*'];
        $httpHeaders = ['Accept: ' . implode(', ', $accept)];
        // Notice we do not use cache here on purpose. We want a fresh view of the RSS stream each time
        $xmlString = getContents($url, $httpHeaders);
        if ($xmlString === '') {
            throw new \Exception(sprintf('Unable to parse xml from `%s` because we got the empty string', $url), 10);
        }
        // Maybe move this call earlier up the stack frames
        // Disable triggering of the php error-handler and handle errors manually instead
        libxml_use_internal_errors(true);
        // Consider replacing libxml with https://www.php.net/domdocument
        // Intentionally not using the silencing operator (@) because it has no effect here
        $xml = simplexml_load_string(trim($xmlString));
        if ($xml === false) {
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $xmlError) {
                Debug::log(trim($xmlError->message));
            }
            if ($xmlErrors) {
                // Render only the first error into exception message
                $firstXmlErrorMessage = $xmlErrors[0]->message;
            }
            throw new \Exception(sprintf('Unable to parse xml from `%s` %s', $url, $firstXmlErrorMessage ?? ''), 11);
        }
        // Restore previous behaviour in case other code relies on it being off
        libxml_use_internal_errors(false);

        $this->parsedFeed = $this->feedParser->parseFeed($xmlString);

        if (isset($xml->item[0])) {
            $this->feedType = self::FEED_TYPE_RSS_1_0;
            $items = $xml->item;
        } elseif (isset($xml->channel[0])) {
            $this->feedType = self::FEED_TYPE_RSS_2_0;
            $items = $xml->channel[0]->item;
        } elseif (isset($xml->entry[0])) {
            $this->feedType = self::FEED_TYPE_ATOM_1_0;
            $items = $xml->entry;
        } else {
            throw new \Exception(sprintf('Unable to detect feed format from `%s`', $url));
        }
        foreach ($items as $item) {
            $parsedItem = $this->parseItem($item);
            if ($parsedItem) {
                $this->items[] = $parsedItem;
            }
            if (count($this->items) >= $maxItems) {
                break;
            }
        }
        return $this;
    }

    /**
     * @param \SimpleXMLElement $item The feed item to be parsed
     */
    protected function parseItem($item)
    {
        switch ($this->feedType) {
            case self::FEED_TYPE_RSS_1_0:
                return $this->feedParser->parseRss1Item($item);
            case self::FEED_TYPE_RSS_2_0:
                return $this->feedParser->parseRss2Item($item);
            case self::FEED_TYPE_ATOM_1_0:
                return $this->feedParser->parseAtomItem($item);
            default:
                throw new \Exception(sprintf('Unknown version %s!', $this->getInput('version')));
        }
    }

    public function getURI()
    {
        return $this->parsedFeed['uri'] ?? parent::getURI();
    }

    public function getName()
    {
        return $this->parsedFeed['title'] ?? parent::getName();
    }

    public function getIcon()
    {
        return $this->parsedFeed['icon'] ?? parent::getIcon();
    }
}
