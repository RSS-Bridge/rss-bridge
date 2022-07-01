<?php

class ZenodoBridge extends BridgeAbstract
{
    const MAINTAINER = 'theradialactive';
    const NAME = 'Zenodo';
    const URI = 'https://zenodo.org';
    const CACHE_TIMEOUT = 10;
    const DESCRIPTION = 'Returns the newest content of Zenodo';

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('div.record-elem.row') as $element) {
            $item = [];
            $item['uri'] = self::URI . $element->find('h4 > a', 0)->href;
            $item['title'] = trim(htmlspecialchars_decode($element->find('h4 > a', 0)->innertext, ENT_QUOTES));

            $authors = $element->find('p', 0);
            if ($authors) {
                $item['author'] = $authors->plaintext;
            }

            $summary = $element->find('p.hidden-xs > a', 0);
            if ($summary) {
                $content = $summary->innertext . '<br>';
            } else {
                $content = 'No content';
            }

            $type = '<br>Type: ' . $element->find('span.label-default', 0)->innertext;
            $item['categories'] = [$element->find('span.label-default', 0)->innertext];

            $raw_date = $element->find('small.text-muted', 0)->innertext;
            $clean_date = str_replace('Uploaded on ', '', $raw_date);

            $content = $content . $raw_date;

            $item['timestamp'] = $clean_date;

            $access = '';
            if ($element->find('span.label-success', 0)) {
                $access = 'Open Access';
            } elseif ($element->find('span.label-warning', 0)) {
                $access = 'Embargoed Access';
            } else {
                $access = $element->find('span.label-error', 0)->innertext;
            }
            $access = '<br>Access: ' . $access;
            $publication = '<br>Publication Date: ' . $element->find('span.label-info', 0)->innertext;
            $item['content'] = $content . $type . $access . $publication;
            $this->items[] = $item;
        }
    }
}
