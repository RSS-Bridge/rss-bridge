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

    public function __construct()
    {
    }

    public static function fromArray(array $itemArray): self
    {
        $item = new self();
        foreach ($itemArray as $key => $value) {
            $item->__set($key, $value);
        }
        return $item;
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

    /**
     * Set URI to the full article.
     *
     * Use {@see FeedItem::getURI()} to get the URI.
     *
     * _Note_: Removes whitespace from the beginning and end of the URI.
     *
     * _Remarks_: Uses the attribute "href" or "src" if the provided URI is an
     * object of simple_html_dom_node.
     *
     * @param simple_html_dom_node|object|string $uri URI to the full article.
     */
    public function setURI($uri)
    {
        $this->uri = null; // Clear previous data

        if ($uri instanceof simple_html_dom_node) {
            if ($uri->hasAttribute('href')) { // Anchor
                $uri = $uri->href;
            } elseif ($uri->hasAttribute('src')) { // Image
                $uri = $uri->src;
            } else {
                Debug::log('The item provided as URI is unknown!');
            }
        }
        if (!is_string($uri)) {
            Debug::log(sprintf('Expected $uri to be string but got %s', gettype($uri)));
            return;
        }
        $uri = trim($uri);
        // Intentionally doing a weak url validation here because FILTER_VALIDATE_URL is too strict
        if (!preg_match('#^https?://#i', $uri)) {
            Debug::log(sprintf('Not a valid url: "%s"', $uri));
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
            Debug::log('Title must be a string!');
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
                Debug::log('Unable to parse timestamp!');
            }
        }
        if ($timestamp <= 0) {
            Debug::log('Timestamp must be greater than zero!');
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
            Debug::log('Author must be a string!');
        } else {
            $this->author = $author;
        }
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|object $content The item content as text or simple_html_dom object.
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
            Debug::log(sprintf('Feed content must be a string but got %s', gettype($content)));
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
            Debug::log('Enclosures must be an array!');
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
                Debug::log('Each enclosure must contain a scheme, host and path!');
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
            Debug::log('Categories must be an array!');
            return;
        }
        foreach ($categories as $category) {
            if (is_string($category)) {
                $this->categories[] = $category;
            } else {
                Debug::log('Category must be a string!');
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
            Debug::log(sprintf('uid must be string: %s (%s)', (string) $uid, var_export($uid, true)));
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
            Debug::log('Key must be a string!');
        } elseif (in_array($name, get_object_vars($this))) {
            Debug::log('Key must be unique!');
        } else {
            $this->misc[$name] = $value;
        }
        return $this;
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
