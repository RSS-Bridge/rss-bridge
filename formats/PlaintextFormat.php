<?php

class PlaintextFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/plain';

    public function render(): string
    {
        $feed = $this->getFeed();
        foreach ($this->getItems() as $item) {
            $feed['items'][] = $item->toArray();
        }
        $text = print_r($feed, true);
        return $text;
    }
}
