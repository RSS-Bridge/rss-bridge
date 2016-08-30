<?php
/**
* Plaintext
* Returns $this->items as raw php data.
*/
class PlaintextFormat extends FormatAbstract{

    public function stringify(){
        $items = $this->getItems();
        return print_r($items, true);
    }

    public function display(){
        $this
            ->setContentType('text/plain;charset=' . $this->getCharset())
            ->callContentType();

        return parent::display();
    }
}