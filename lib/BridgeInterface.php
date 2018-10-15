<?php
interface BridgeInterface {

	/**
	 * Collects data from the site
	 */
	public function collectData();

	/**
	 * Returns the description
	 *
	 * @return string Description
	 */
	public function getDescription();

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
	 * Returns the cache timeout
	 *
	 * @return int Cache timeout
	 */
	public function getCacheTimeout();
}
