<?php

/**
 * M3U
 *
 */
class M3uFormat extends FormatAbstract
{
    const MIME_TYPE = 'application/mpegurl';

    public function stringify()
    {
        $contents = "#EXTM3U\n";

        foreach ($this->getItems() as $item) {
            $itemArray = $item->toArray();

            if (isset($itemArray['itunes']) && isset($itemArray['enclosure'])) {
                $contents .= $itemArray['enclosure']['url'] . "\n";
            }
        }
        return mb_convert_encoding($contents, $this->getCharset(), 'UTF-8');
    }
}
