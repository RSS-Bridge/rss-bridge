<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license https://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

/**
 * An abstract class for format implementations
 *
 * This class implements {@see FormatInterface}
 */
abstract class FormatAbstract implements FormatInterface
{
    /** The default charset (UTF-8) */
    const DEFAULT_CHARSET = 'UTF-8';

    /** MIME type of format output */
    const MIME_TYPE = 'text/plain';

    /** @var string $charset The charset */
    protected $charset;

    /** @var array $items The items */
    protected $items;

    /**
     * @var int $lastModified A timestamp to indicate the last modified time of
     * the output data.
     */
    protected $lastModified;

    /** @var array $extraInfos The extra infos */
    protected $extraInfos;

    /** {@inheritdoc} */
    public function getMimeType()
    {
        return static::MIME_TYPE;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $charset {@inheritdoc}
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /** {@inheritdoc} */
    public function getCharset()
    {
        $charset = $this->charset;

        if (is_null($charset)) {
            return static::DEFAULT_CHARSET;
        }
        return $charset;
    }

    /**
     * Set the last modified time
     *
     * @param int $lastModified The last modified time
     * @return void
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $items {@inheritdoc}
     */
    public function setItems(array $items)
    {
        $this->items = $items;

        return $this;
    }

    /** {@inheritdoc} */
    public function getItems()
    {
        if (!is_array($this->items)) {
            throw new \LogicException(sprintf('Feed the %s with "setItems" method before !', get_class($this)));
        }

        return $this->items;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $extraInfos {@inheritdoc}
     */
    public function setExtraInfos(array $extraInfos = [])
    {
        foreach (['name', 'uri', 'icon', 'donationUri'] as $infoName) {
            if (!isset($extraInfos[$infoName])) {
                $extraInfos[$infoName] = '';
            }
        }

        $this->extraInfos = $extraInfos;

        return $this;
    }

    /** {@inheritdoc} */
    public function getExtraInfos()
    {
        if (is_null($this->extraInfos)) { // No extra info ?
            $this->setExtraInfos(); // Define with default value
        }

        return $this->extraInfos;
    }
}
