<?php
/**
* Json
* Builds a JSON string from $this->items and return it to browser.
*
* @name Json
*/
class JsonFormat extends FormatAbstract{

    public function stringify(){
        // FIXME : sometime content can be null, transform to empty string
        $datas = $this->getDatas();

        return json_encode($datas, JSON_PRETTY_PRINT);
    }

    public function display(){
        $this
            ->setContentType('application/json')
            ->callContentType();

        return parent::display();
    }
}
