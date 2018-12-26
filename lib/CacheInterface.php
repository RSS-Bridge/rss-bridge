<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	http://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

/**
 * The cache interface
 *
 * @todo Add missing function to the interface
 * @todo Explain parameters and return values in more detail
 * @todo Return self more often (to allow call chaining)
 */
interface CacheInterface {
	/**
	 * Loads data from cache
	 *
	 * @return mixed The cache data
	 */
	public function loadData();

	/**
	 * Stores data to the cache
	 *
	 * @param mixed $datas The data to store
	 * @return self The cache object
	 */
	public function saveData($datas);

	/**
	 * Returns the timestamp for the curent cache file
	 *
	 * @return int Timestamp
	 */
	public function getTime();

	/**
	 * Removes any data that is older than the specified duration from cache
	 *
	 * @param int $duration The cache duration in seconds
	 */
	public function purgeCache($duration);
}
