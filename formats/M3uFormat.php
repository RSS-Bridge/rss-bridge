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

            $m3uitem = new M3uItem();

            if (isset($itemArray['enclosure'])) {
                $m3uitem->url = $itemArray['enclosure']['url'];
                $m3uitem->bytes = $itemArray['enclosure']['length'];
            }
            if (isset($itemArray['itunes']) && isset($itemArray['itunes']['duration'])) {
                $m3uitem->duration = parse_duration($itemArray['itunes']['duration']);
            }
            if (isset($itemArray['title'])) {
                $m3uitem->title = $itemArray['title'];
            }
            $contents .= $m3uitem->render();
        }
        return mb_convert_encoding($contents, $this->getCharset(), 'UTF-8');
    }
}

/*
 * parse_duration converts a string like "00:4:20" to 260
 * allowing to convert duration as used in the itunes:duration tag to the number of seconds
 */
function parse_duration(string $duration_string): int
{
    $seconds = 0;
    $parts = explode(':', $duration_string);
    for ($i = 0; $i < count($parts); $i++) {
        $seconds += intval($parts[count($parts) - $i - 1]) * pow(60, $i);
    }
    return $seconds;
}

class M3uItem
{
    public $duration = null;
    public $title = null;
    public $url = null;
    public $bytes = null;

    public function render(): string
    {
        if ($this->url === null) {
            return '';
        }
        $text = "\n";
        $commentParts = [];
        if ($this->duration !== null && $this->duration > 0) {
            $commentParts[] = $this->duration;
        }
        if ($this->title !== null) {
            $commentParts[] = $this->title;
        }

        if (count($commentParts) !== 0) {
            $text .= '#EXTINF:' . implode(',', $commentParts) . "\n";
        }

        $text .= $this->url . "\n";
        return $text;
    }
}
