<?php
/**
* Json
* Builds a JSON string from $this->items and return it to browser.
*/
class JsonFormat extends FormatAbstract {

	public function stringify(){
		$items = $this->getItems();
		$toReturn = json_encode($items, JSON_PRETTY_PRINT);

		// Remove invalid non-UTF8 characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}

	public function display(){
		$this
			->setContentType('application/json; charset=' . $this->getCharset())
			->callContentType();

		return parent::display();
	}
}
