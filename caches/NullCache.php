<?php
/**
* Do-nothing-cache
*/
class NullCache implements CacheInterface {
	/**
	 * {@inheritdoc}
	 */
	public function setScope($scope) {}

	/**
	 * {@inheritdoc}
	 */
	public function setKey($key) {}

	/**
	 * {@inheritdoc}
	 */
	public function loadData() {
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveData($data) {}

	/**
	 * {@inheritdoc}
	 */
	public function getTime() {
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function purgeCache($seconds) {}
}
