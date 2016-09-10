<?php
require_once(__DIR__ . '/CacheInterface.php');
abstract class CacheAbstract implements CacheInterface {
	protected $param;

	public function prepare(array $param){
		$this->param = $param;

		return $this;
	}
}
