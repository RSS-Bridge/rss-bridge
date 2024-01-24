<?php

class EDDHPiRepsBridge extends BridgeAbstract
{
    const NAME = 'EDDH.de PIREPs';
    const URI = 'https://eddh.de/info/pireps_08days.php';
    const DESCRIPTION = 'Erfahrungen und Tipps von Piloten für Piloten: Die Einträge der letzten 8 Tage';
    const MAINTAINER = 'hleskien';
    //const PARAMETERS = [];
    //const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI);
        foreach ($dom->find('table table table td') as $itemnode) {
            $texts = $this->extractTexts($itemnode->find('text, br'));
            $timestamp = $itemnode->find('.su_dat', 0)->innertext();
            $uri = $itemnode->find('.pir_hd a', 0)->href;
            $this->items[] = [
                'timestamp' => $this->formatItemTimestamp($timestamp),
                'title' => $this->formatItemTitle($texts),
                'uri' => $this->formatItemUri($uri),
                'author' => $this->formatItemAuthor($texts),
                'content' => $this->formatItemContent($texts)
            ];
        }
    }

    public function getIcon()
    {
        return 'https://eddh.de/favicon.ico';
    }

    private function extractTexts($nodes)
    {
        $texts = [];
        $i = 0;
        foreach ($nodes as $node) {
            $text = trim($node->outertext());
            if ($node->tag == 'br') {
                $texts[$i++] = "\n";
            } elseif (($node->tag == 'text') && ($text != '')) {
                $text = iconv('Windows-1252', 'UTF-8', $text);
                $text = str_replace('&nbsp;', '', $text);
                $texts[$i++] = $text;
            }
        }
        return $texts;
    }

    protected function formatItemAuthor($texts)
    {
        $pos = array_search('Name:', $texts);
        return $texts[$pos + 1];
    }

    protected function formatItemContent($texts)
    {
        $pos1 = array_search('Bemerkungen:', $texts);
        $pos2 = array_search('Bewertung:', $texts);
        $content = '';
        for ($i = $pos1 + 1; $i < $pos2; $i++) {
            $content .= $texts[$i];
        }
        return trim($content);
    }

    protected function formatItemTitle($texts)
    {
        $texts[5] = ltrim($texts[5], '(');
        return implode(' ', [$texts[1], $texts[2], $texts[3], $texts[5]]);
    }

    protected function formatItemTimestamp($value)
    {
        $value = str_replace('Eintrag vom', '', $value);
        $value = trim($value);
        return strtotime($value);
    }

    protected function formatItemUri($value)
    {
        return 'https://eddh.de/info/' . $value;
    }
}
