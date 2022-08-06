<?php

class PlaintextFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/plain';

    public function stringify()
    {
        $items = $this->getItems();
        $data = [];

        foreach ($items as $item) {
            $data[] = $item->toArray();
        }

        $toReturn = print_r($data, true);

        // Remove invalid non-UTF8 characters
        ini_set('mbstring.substitute_character', 'none');
        $toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
        return $toReturn;
    }
}
