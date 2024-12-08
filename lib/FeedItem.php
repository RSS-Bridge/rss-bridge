<?php

class FeedItem
{
    protected ?string $uri = null;
    protected ?string $title = null;
    protected ?int $timestamp = null;
    protected ?string $author = null;
    protected ?string $content = null;
    protected array $enclosures = [];
    protected array $categories = [];
    protected ?string $uid = null;
    protected array $misc = [];

    private Logger $logger;

    public static function fromArray(array $itemArray): self
    {
        $item = new self();
        foreach ($itemArray as $key => $value) {
            $item->__set($key, $value);
        }
        return $item;
    }

    private function __construct()
    {
        global $container;

        // The default NullLogger is for when running the unit tests
        $this->logger = $container['logger'] ?? new NullLogger();
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'uri':
                $this->setURI($value);
                break;
            case 'title':
                $this->setTitle($value);
                break;
            case 'timestamp':
                $this->setTimestamp($value);
                break;
            case 'author':
                $this->setAuthor($value);
                break;
            case 'content':
                $this->setContent($value);
                break;
            case 'enclosures':
                $this->setEnclosures($value);
                break;
            case 'categories':
                $this->setCategories($value);
                break;
            case 'uid':
                $this->setUid($value);
                break;
            default:
                $this->addMisc($name, $value);
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'uri':
                return $this->getURI();
            case 'title':
                return $this->getTitle();
            case 'timestamp':
                return $this->getTimestamp();
            case 'author':
                return $this->getAuthor();
            case 'content':
                return $this->getContent();
            case 'enclosures':
                return $this->getEnclosures();
            case 'categories':
                return $this->getCategories();
            case 'uid':
                return $this->getUid();
            default:
                if (array_key_exists($name, $this->misc)) {
                    return $this->misc[$name];
                }
                return null;
        }
    }

    public function getURI(): ?string
    {
        return $this->uri;
    }

    public function setURI($uri)
    {
        $this->uri = null; // Clear previous data

        if ($uri instanceof simple_html_dom_node) {
            if ($uri->hasAttribute('href')) { // Anchor
                $uri = $uri->href;
            } elseif ($uri->hasAttribute('src')) { // Image
                $uri = $uri->src;
            } else {
                $this->logger->debug('The item provided as URI is unknown!');
            }
        }
        if (!is_string($uri)) {
            $this->logger->debug(sprintf('Expected $uri to be string but got %s', gettype($uri)));
            return;
        }
        $uri = trim($uri);
        // Intentionally doing a weak url validation here because FILTER_VALIDATE_URL is too strict
        if (!preg_match('#^https?://#i', $uri)) {
            $this->logger->debug(sprintf('Not a valid url: "%s"', $uri));
            return;
        }
        $this->uri = $uri;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = null;
        if (!is_string($title)) {
            $this->logger->debug('Title must be a string: ' . print_r($title, true));
        } else {
            $this->title = truncate(trim($title));
        }
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function setTimestamp($datetime)
    {
        $this->timestamp = null;
        if (is_numeric($datetime)) {
            $timestamp = $datetime;
        } else {
            $timestamp = strtotime($datetime);
            if ($timestamp === false) {
                $this->logger->debug('Unable to parse timestamp!');
            }
        }
        if ($timestamp <= 0) {
            $this->logger->debug('Timestamp must be greater than zero!');
        } else {
            $this->timestamp = $timestamp;
        }
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = null;
        if (!is_string($author)) {
            $this->logger->debug('Author must be a string!');
        } else {
            $this->author = $author;
        }
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|array|\simple_html_dom|\simple_html_dom_node $content The item content
     */
    public function setContent($content)
    {
        $this->content = null;

        if (
            $content instanceof simple_html_dom
            || $content instanceof simple_html_dom_node
        ) {
            $content = (string) $content;
        }

        if (is_string($content)) {
            $this->content = $content;
        } else {
            $this->logger->debug(sprintf('Unable to convert feed content to string: %s', gettype($content)));
        }
    }

    public function getEnclosures(): array
    {
        return $this->enclosures;
    }

    public function setEnclosures($enclosures)
    {
        $this->enclosures = [];

        if (!is_array($enclosures)) {
            $this->logger->debug('Enclosures must be an array!');
            return;
        }
        foreach ($enclosures as $enclosure) {
            if (
                !filter_var(
                    $enclosure,
                    FILTER_VALIDATE_URL,
                    FILTER_FLAG_PATH_REQUIRED
                )
            ) {
                $this->logger->debug('Each enclosure must contain a scheme, host and path!');
            } elseif (!in_array($enclosure, $this->enclosures)) {
                $this->enclosures[] = $enclosure;
            }
        }
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories($categories)
    {
        $this->categories = [];

        if (!is_array($categories)) {
            $this->logger->debug('Categories must be an array!');
            return;
        }
        foreach ($categories as $category) {
            if (is_string($category)) {
                $this->categories[] = $category;
            } else {
                $this->logger->debug('Category must be a string!');
            }
        }
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid($uid): void
    {
        $this->uid = null;
        if (!is_string($uid)) {
            $this->logger->debug(sprintf('uid must be string: %s (%s)', (string) $uid, var_export($uid, true)));
            return;
        }
        if (preg_match('/^[a-f0-9]{40}$/', $uid)) {
            // Preserve sha1 hash
            $this->uid = $uid;
        } else {
            $this->uid = sha1($uid);
        }
    }

    public function addMisc($name, $value)
    {
        if (!is_string($name)) {
            $this->logger->debug('Key must be a string!');
        } elseif (in_array($name, get_object_vars($this))) {
            $this->logger->debug('Key must be unique!');
        } else {
            $this->misc[$name] = $value;
        }
    }

    public function toArray(): array
    {
        return array_merge(
            [
                'uri' => $this->uri,
                'title' => $this->title,
                'timestamp' => $this->timestamp,
                'author' => $this->author,
                'content' => $this->content,
                'enclosures' => $this->enclosures,
                'categories' => $this->categories,
                'uid' => $this->uid,
            ],
            $this->misc
        );
    }
}
