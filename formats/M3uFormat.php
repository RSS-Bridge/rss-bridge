<?php

/**
 * M3U
 *
 */
class M3uFormat extends FormatAbstract
{
    const MIME_TYPE = 'application/mpegurl';
    private $item_duration = null;
    private $item_title = null;
    private $item_url = null;
    private $item_bytes = null;

    private function resetItem()
    {
        $this->item_duration = null;
        $this->item_title = null;
        $this->item_url = null;
        $this->item_bytes = null;
    }
    private function itemIsEmpty(): bool
    {
        return $this->item_url === null;
    }
    private function itemRender(): string
    {
        if ($this->itemIsEmpty()) {
            return '';
        }
        $text = "\n";
        $commentParts = [];
        if ($this->item_duration !== null && $this->item_duration > 0) {
            $commentParts[] = $this->item_duration;
        }
        if ($this->item_title !== null) {
            $commentParts[] = $this->item_title;
        }

        if (count($commentParts) !== 0) {
            $text .= '#EXTINF:' . implode(',', $commentParts) . "\n";
        }

        $text .= $this->item_url . "\n";
        return $text;
    }

    public function stringify()
    {
        $contents = "#EXTM3U\n";

        foreach ($this->getItems() as $item) {
            $this->resetItem();
            $itemArray = $item->toArray();

            if (isset($itemArray['enclosure'])) {
                $this->item_url = $itemArray['enclosure']['url'];
                $this->item_bytes = $itemArray['enclosure']['length'];
                if (isset($itemArray['itunes']) && isset($itemArray['itunes']['duration'])) {
                    $this->item_duration = self::parseDuration($itemArray['itunes']['duration']);
                }
            }
            if (isset($itemArray['title'])) {
                $item->item_title = $itemArray['title'];
            }
            if (! $this->itemIsEmpty()) {
                $contents .= $this->itemRender();
            } else {
                foreach ($item->enclosures as $url) {
                    $this->resetItem();
                    $this->item_url = $url;
                    if (isset($itemArray['title'])) {
                        $this->item_title = $itemArray['title'];
                    }
                    $contents .= $this->itemRender();
                }
            }
        }
        return mb_convert_encoding($contents, $this->getCharset(), 'UTF-8');
    }
    /*
     * parseDuration converts a string like "00:4:20" to 260
     * allowing to convert duration as used in the itunes:duration tag to the number of seconds
     */
    private static function parseDuration(string $duration_string): int
    {
        $seconds = 0;
        $parts = explode(':', $duration_string);
        for ($i = 0; $i < count($parts); $i++) {
            $seconds += intval($parts[count($parts) - $i - 1]) * pow(60, $i);
        }
        return $seconds;
    }
}
