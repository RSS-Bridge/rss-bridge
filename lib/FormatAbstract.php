<?php

abstract class FormatAbstract
{
    const MIME_TYPE = 'text/plain';

    protected string $charset = 'UTF-8';
    protected array $items = [];
    protected int $lastModified;
    protected array $extraInfos = [];

    abstract public function stringify();

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

    public function setExtraInfos(array $infos = [])
    {
        $extras = [
            'name',
            'uri',
            'icon',
            'donationUri',
        ];
        foreach ($extras as $extra) {
            if (!isset($infos[$extra])) {
                $infos[$extra] = '';
            }
        }
        $this->extraInfos = $infos;
    }

    public function getExtraInfos(): array
    {
        if (!$this->extraInfos) {
            $this->setExtraInfos();
        }
        return $this->extraInfos;
    }
}
