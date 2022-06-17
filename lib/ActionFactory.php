<?php

class ActionFactory
{
	private $folder;

	public function __construct(string $folder = PATH_LIB_ACTIONS)
	{
		$this->folder = $folder;
	}

	public function create(string $name)
	{
		$name = ucfirst(strtolower($name)) . 'Action';
		$filePath = $this->folder . $name . '.php';
		if(!file_exists($filePath)) {
			throw new \Exception('Invalid action');
		}
		$className = '\\' . $name;
		return new $className();
	}
}
