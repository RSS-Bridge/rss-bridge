<?php

/**
 * Expands an existing feed
 */
abstract class FeedExpander extends BridgeAbstract
{
    const FEED_TYPE_RSS_1_0 = 'RSS_1_0';
    const FEED_TYPE_RSS_2_0 = 'RSS_2_0';
    const FEED_TYPE_ATOM_1_0 = 'ATOM_1_0';

    private $title;
    private $uri;
    private $icon;
    private $feedType;
    private FeedParser $feedParser;

    public function __construct(CacheInterface $cache, Logger $logger)
    {
        parent::__construct($cache, $logger);
        $this->feedParser = new FeedParser();
    }

    public function collectExpandableDatas($url, $maxItems = -1)
    {
        if (empty($url)) {
            throw new \Exception('There is no $url for this RSS expander');
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

        if (isset($xml->item[0])) {
            $this->feedType = self::FEED_TYPE_RSS_1_0;
            // loadRss2Data
            $channel = $xml->channel[0];
            $this->title = trim((string)$channel->title);
            $this->uri = trim((string)$channel->link);
            if (!empty($channel->image)) {
                $this->icon = trim((string)$channel->image->url);
            }
            // todo: set title, link, description, language, and so on
            foreach ($xml->item as $item) {
                $parsedItem = $this->parseItem($item);
                if (!empty($parsedItem)) {
                    $this->items[] = $parsedItem;
                }
                if ($maxItems !== -1 && count($this->items) >= $maxItems) {
                    break;
                }
            }
        } elseif (isset($xml->channel[0])) {
            $this->feedType = self::FEED_TYPE_RSS_2_0;
            // loadRss2Data
            $channel = $xml->channel[0];
            $this->title = trim((string)$channel->title);
            $this->uri = trim((string)$channel->link);
            if (!empty($channel->image)) {
                $this->icon = trim((string)$channel->image->url);
            }
            // todo: set title, link, description, language, and so on
            foreach ($channel->item as $item) {
                $parsedItem = $this->parseItem($item);
                if (!empty($parsedItem)) {
                    $this->items[] = $parsedItem;
                }
                if ($maxItems !== -1 && count($this->items) >= $maxItems) {
                    break;
                }
            }
        } elseif (isset($xml->entry[0])) {
            $this->feedType = self::FEED_TYPE_ATOM_1_0;
            // loadAtomData
            $this->title = (string)$xml->title;
            // Find best link (only one, or first of 'alternate')
            if (!isset($xml->link)) {
                $this->uri = '';
            } elseif (count($xml->link) === 1) {
                $this->uri = (string)$xml->link[0]['href'];
            } else {
                $this->uri = '';
                foreach ($xml->link as $link) {
                    if (strtolower($link['rel']) === 'alternate') {
                        $this->uri = (string)$link['href'];
                        break;
                    }
                }
            }
            if (!empty($xml->icon)) {
                $this->icon = (string)$xml->icon;
            } elseif (!empty($xml->logo)) {
                $this->icon = (string)$xml->logo;
            }
            // parse items
            foreach ($xml->entry as $item) {
                $parsedItem = $this->parseItem($item);
                if (!empty($parsedItem)) {
                    $this->items[] = $parsedItem;
                }
                if ($maxItems !== -1 && count($this->items) >= $maxItems) {
                    break;
                }
            }
        } else {
            throw new \Exception(sprintf('Unable to detect feed format from `%s`', $url));
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
        if (!empty($this->uri)) {
            return $this->uri;
        }
        return parent::getURI();
    }

    public function getName()
    {
        if (!empty($this->title)) {
            return $this->title;
        }
        return parent::getName();
    }

    public function getIcon()
    {
        if (!empty($this->icon)) {
            return $this->icon;
        }
        return parent::getIcon();
    }
}
