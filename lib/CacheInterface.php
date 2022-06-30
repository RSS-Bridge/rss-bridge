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
    /**
     * Set scope of the current cache
     *
     * If $scope is an empty string, the cache is set to a global context.
     *
     * @param string $scope The scope the data is related to
     */
    public function setScope($scope);

    /**
     * Set key to assign the current data
     *
     * Since $key can be anything, the cache implementation must ensure to
     * assign the related data reliably; most commonly by serializing and
     * hashing the key in an appropriate way.
     *
     * @param array $key The key the data is related to
     */
    public function setKey($key);

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
     * Returns the timestamp for the curent cache data
     *
     * @return int Timestamp or null
     */
    public function getTime();

    /**
     * Removes any data that is older than the specified age from cache
     *
     * @param int $seconds The cache age in seconds
     */
    public function purgeCache($seconds);
}
