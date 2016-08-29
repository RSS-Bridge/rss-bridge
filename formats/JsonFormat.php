<?php
/**
* Json
* Builds a JSON string from $this->items and return it to browser.
*/
class JsonFormat extends FormatAbstract{

    public function stringify(){
        // FIXME : sometime content can be null, transform to empty string
        $items = $this->getItems();

        return json_encode($items, JSON_PRETTY_PRINT);
    }

    public function display(){
        $this
            ->setContentType('application/json')
            ->callContentType();

        return parent::display();
    }
}
