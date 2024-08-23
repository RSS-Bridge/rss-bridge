<?php

class HtmlFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/html';

    public function render(): string
    {
        // This query string is url encoded
        $queryString = $_SERVER['QUERY_STRING'];

        // TODO: this should be the proper bridge short name and not user provided string
        $bridgeName = $_GET['bridge'];

        $feedArray = $this->getFeed();
        $formatFactory = new FormatFactory();
        $formats = [];

        // Create all formats (except HTML)
        $formatNames = $formatFactory->getFormatNames();
        foreach ($formatNames as $formatName) {
            if ($formatName === 'Html') {
                continue;
            }
            // The format url is relative, but should be absolute in order to help feed readers.
            $formatUrl = '?' . str_ireplace('format=Html', 'format=' . $formatName, $queryString);
            $formatObject = $formatFactory->create($formatName);
            $formats[] = [
                'url'       => $formatUrl,
                'name'      => $formatName,
                'type'      => $formatObject->getMimeType(),
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

        $donationUri = null;
        if (Configuration::getConfig('admin', 'donations') && $feedArray['donationUri']) {
            $donationUri = $feedArray['donationUri'];
        }

        $html = render_template(__DIR__ . '/../templates/html-format.html.php', [
            'bridge_name'   => $bridgeName,
            'title'         => $feedArray['name'],
            'formats'       => $formats,
            'uri'           => $feedArray['uri'],
            'items'         => $items,
            'donation_uri'  => $donationUri,
        ]);
        return $html;
    }
}
