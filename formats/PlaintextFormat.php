<?php

class PlaintextFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/plain';

    public function stringify(?Request $request)
    {
        $feed = $this->getFeed();
        foreach ($this->getItems() as $item) {
            $feed['items'][] = $item->toArray();
        }
        $text = print_r($feed, true);
        // Remove invalid non-UTF8 characters
        ini_set('mbstring.substitute_character', 'none');
        $text = mb_convert_encoding($text, $this->getCharset(), 'UTF-8');
        return $text;
    }
}
