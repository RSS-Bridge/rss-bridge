<?php

class HtmlFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/html';

    public function stringify()
    {
        $queryString = $_SERVER['QUERY_STRING'];

        $feedArray = $this->getFeed();
        $formatFactory = new FormatFactory();
        $buttons = [];
        $linkTags = [];
        foreach ($formatFactory->getFormatNames() as $formatName) {
            // Dynamically build buttons for all formats (except HTML)
            if ($formatName === 'Html') {
                continue;
            }
            $formatUrl = '?' . str_ireplace('format=Html', 'format=' . $formatName, htmlentities($queryString));
            $buttons[] = [
                'href' => $formatUrl,
                'value' => $formatName,
            ];
            $format = $formatFactory->create($formatName);
            $linkTags[] = [
                'href' => $formatUrl,
                'title' => $formatName,
                'type' => $format->getMimeType(),
            ];
        }

        if (Configuration::getConfig('admin', 'donations') && $feedArray['donationUri']) {
            $buttons[] = [
                'href' => e($feedArray['donationUri']),
                'value' => 'Donate to maintainer',
            ];
        }

        $items = [];
        foreach ($this->getItems() as $item) {
            $items[] = [
                'url'           => $item->getURI() ?: $feedArray['uri'],
                'title'         => $item->getTitle() ?? '(no title)',
                'timestamp'     => $item->getTimestamp(),
                'author'        => $item->getAuthor(),
                'content'       => $item->getContent() ?? '',
                'enclosures'    => $item->getEnclosures(),
                'categories'    => $item->getCategories(),
            ];
        }

        $html = render_template(__DIR__ . '/../templates/html-format.html.php', [
            'charset'   => $this->getCharset(),
            'title'     => $feedArray['name'],
            'linkTags'  => $linkTags,
            'uri'       => $feedArray['uri'],
            'buttons'   => $buttons,
            'items'     => $items,
        ]);
        // Remove invalid characters
        ini_set('mbstring.substitute_character', 'none');
        $html = mb_convert_encoding($html, $this->getCharset(), 'UTF-8');
        return $html;
    }
}
