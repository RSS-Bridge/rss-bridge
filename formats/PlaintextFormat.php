<?php
/**
* Plaintext
* Returns $this->items as raw php data.
*/
class PlaintextFormat extends FormatAbstract{

    public function stringify(){
        $datas = $this->getDatas();
        return print_r($datas, true);
    }

    public function display(){
        $this
            ->setContentType('text/plain;charset=' . $this->getCharset())
            ->callContentType();

        return parent::display();
    }
}