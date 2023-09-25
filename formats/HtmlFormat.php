<?php

class HtmlFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/html';

    public function stringify()
    {
        $queryString = $_SERVER['QUERY_STRING'];

        $extraInfos = $this->getExtraInfos();
        $formatFactory = new FormatFactory();
        $buttons = [];
        $linkTags = [];
        foreach ($formatFactory->getFormatNames() as $format) {
            // Dynamically build buttons for all formats (except HTML)
            if ($format === 'Html') {
                continue;
            }
            $formatUrl = '?' . str_ireplace('format=Html', 'format=' . $format, htmlentities($queryString));
            $buttons[] = [
                'href' => $formatUrl,
                'value' => $format,
            ];
            $linkTags[] = [
                'href' => $formatUrl,
                'title' => $format,
                'type' => $formatFactory->create($format)->getMimeType(),
            ];
        }

        if (Configuration::getConfig('admin', 'donations') && $extraInfos['donationUri'] !== '') {
            $buttons[] = [
                'href' => e($extraInfos['donationUri']),
                'value' => 'Donate to maintainer',
            ];
        }

        $items = [];
        foreach ($this->getItems() as $item) {
            $items[] = [
                'url'           => $item->getURI() ?: $extraInfos['uri'],
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
            'title'     => $extraInfos['name'],
            'linkTags'  => $linkTags,
            'uri'       => $extraInfos['uri'],
            'buttons'   => $buttons,
            'items'     => $items,
        ]);
        // Remove invalid characters
        ini_set('mbstring.substitute_character', 'none');
        $html = mb_convert_encoding($html, $this->getCharset(), 'UTF-8');
        return $html;
    }
}
