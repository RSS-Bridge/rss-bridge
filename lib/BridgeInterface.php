<?php
interface BridgeInterface {

	/**
	 * Collects data from the site
	 */
	public function collectData();

	/**
	 * Returns an array of cachable elements
	 *
	 * @return array Associative array of cachable elements
	 */
	public function getCachable();

	/**
	 * Returns the description
	 *
	 * @return string Description
	 */
	public function getDescription();

	/**
	 * Return an array of extra information
	 *
	 * @return array Associative array of extra information
	 */
	public function getExtraInfos();

	/**
	 * Returns an array of collected items
	 *
	 * @return array Associative array of items
	 */
	public function getItems();

	/**
	 * Returns the bridge maintainer
	 *
	 * @return string Bridge maintainer
	 */
	public function getMaintainer();

	/**
	 * Returns the bridge name
	 *
	 * @return string Bridge name
	 */
	public function getName();

	/**
	 * Returns the bridge icon
	 *
	 * @return string Bridge icon
	 */
	public function getIcon();

	/**
	 * Returns the bridge parameters
	 *
	 * @return array Bridge parameters
	 */
	public function getParameters();

	/**
	 * Returns the bridge URI
	 *
	 * @return string Bridge URI
	 */
	public function getURI();

	/**
	 * Sets the cache instance
	 *
	 * @param object CacheInterface The cache instance
	 */
	public function setCache(\CacheInterface $cache);

	/**
	 * Sets the timeout for clearing the cache files. The timeout must be
	 * specified between 1..86400 seconds (max. 24 hours). The default timeout
	 * (specified by the bridge maintainer) applies for invalid values.
	 *
	 * @param int $timeout The cache timeout in seconds
	 */
	public function setCacheTimeout($timeout);

	/**
	 * Returns the cache timeout
	 *
	 * @return int Cache timeout
	 */
	public function getCacheTimeout();
}
