<?php

abstract class FormatAbstract
{
    public const ITUNES_NS = 'http://www.itunes.com/dtds/podcast-1.0.dtd';

    const MIME_TYPE = 'text/plain';

    protected string $charset = 'UTF-8';
    protected array $items = [];
    protected int $lastModified;

    protected array $feed = [];

    abstract public function stringify(?Request $request);

    public function setFeed(array $feed)
    {
        $default = [
            'name'          => '',
            'uri'           => '',
            'icon'          => '',
            'donationUri'   => '',
        ];
        $this->feed = array_merge($default, $feed);
    }

    public function getFeed(): array
    {
        return $this->feed;
    }

    /**
     * @param FeedItem[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return FeedItem[] The items
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getMimeType(): string
    {
        return static::MIME_TYPE;
    }

    public function setCharset(string $charset)
    {
        $this->charset = $charset;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setLastModified(int $lastModified)
    {
        $this->lastModified = $lastModified;
    }
}
