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

/**
 * The format interface
 *
 * @todo Add missing function to the interface
 * @todo Explain parameters and return values in more detail
 * @todo Return self more often (to allow call chaining)
 */
interface FormatInterface
{
    /**
     * Generate a string representation of the current data
     *
     * @return string The string representation
     */
    public function stringify();

    /**
     * Display the current data to the user
     *
     * @return self The format object
     */
    public function display();

    /**
     * Set items
     *
     * @param array $bridges The items
     * @return self The format object
     *
     * @todo Rename parameter `$bridges` to `$items`
     */
    public function setItems(array $bridges);

    /**
     * Return items
     *
     * @throws \LogicException if the items are not set
     * @return array The items
     */
    public function getItems();

    /**
     * Set extra information
     *
     * @param array $infos Extra information
     * @return self The format object
     */
    public function setExtraInfos(array $infos);

    /**
     * Return extra information
     *
     * @return array Extra information
     */
    public function getExtraInfos();

    /**
     * Return MIME type
     *
     * @return string The MIME type
     */
    public function getMimeType();

    /**
     * Set charset
     *
     * @param string $charset The charset
     * @return self The format object
     */
    public function setCharset($charset);

    /**
     * Return current charset
     *
     * @return string The charset
     */
    public function getCharset();
}
