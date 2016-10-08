<?php
interface CacheInterface {
	public function loadData();
	public function saveData($datas);
	public function getTime();
	public function purgeCache($duration);
}
