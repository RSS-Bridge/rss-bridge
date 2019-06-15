<?php
/**
* Plaintext
* Returns $this->items as raw php data.
*/
class PlaintextFormat extends FormatAbstract {
	public function stringify(){
		$items = $this->getItems();
		$data = array();

		foreach($items as $item) {
			$data[] = $item->toArray();
		}

		$toReturn = print_r($data, true);

		// Remove invalid non-UTF8 characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}

	public function display(){
		$this
			->setContentType('text/plain; charset=' . $this->getCharset())
			->callContentType();

		return parent::display();
	}
}
