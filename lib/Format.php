<?php
require_once(__DIR__ . '/FormatInterface.php');
class Format {

	static protected $dirFormat;

	public function __construct(){
		throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
	}

	static public function create($nameFormat){
		if(!preg_match('@^[A-Z][a-zA-Z]*$@', $nameFormat)){
			throw new \InvalidArgumentException('Name format must be at least
 one uppercase follow or not by alphabetic characters.');
		}

		$nameFormat = $nameFormat . 'Format';
		$pathFormat = self::getDir() . $nameFormat . '.php';

		if(!file_exists($pathFormat)){
			throw new \Exception('The format you looking for does not exist.');
		}

		require_once $pathFormat;

		return new $nameFormat();
	}

	static public function setDir($dirFormat){
		if(!is_string($dirFormat)){
			throw new \InvalidArgumentException('Dir format must be a string.');
		}

		if(!file_exists($dirFormat)){
			throw new \Exception('Dir format does not exist.');
		}

		self::$dirFormat = $dirFormat;
	}

	static public function getDir(){
		$dirFormat = self::$dirFormat;

		if(is_null($dirFormat)){
			throw new \LogicException(__CLASS__ . ' class need to know format path !');
		}

		return $dirFormat;
	}

	/**
	* Read format dir and catch informations about each format depending annotation
	* @return array Informations about each format
	*/
	static public function searchInformation(){
		$pathDirFormat = self::getDir();

		$listFormat = array();

		$searchCommonPattern = array('name');

		$dirFiles = scandir($pathDirFormat);
		if($dirFiles !== false){
			foreach($dirFiles as $fileName){
				if(preg_match('@^([^.]+)Format\.php$@U', $fileName, $out)){ // Is PHP file ?
					$listFormat[] = $out[1];
				}
			}
		}

		return $listFormat;
	}
}
