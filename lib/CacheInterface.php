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
 * The cache interface
 */
interface CacheInterface
{
    public function setScope(string $scope): void;

    public function setKey(array $key): void;

    /**
     * Loads data from cache
     *
     * @return mixed The cached data or null
     */
    public function loadData();

    /**
     * Stores data to the cache
     *
     * @param mixed $data The data to store
     * @return self The cache object
     */
    public function saveData($data);

    /**
     * Returns the modification time of the current cache item.
     * In unix timestamp.
     * Example: 1688570578
     */
    public function getTime(): ?int;

    /**
     * Removes any data that is older than the specified age from cache
     *
     * @param int $seconds The cache age in seconds
     */
    public function purgeCache($seconds);
}
