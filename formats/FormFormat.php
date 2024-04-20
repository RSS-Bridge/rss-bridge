<?php

class FormFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/html';

    public function stringify()
    {
        // This query string is url encoded
        $queryString = $_SERVER['QUERY_STRING'];

        $feedArray = $this->getFeed();
        $formatFactory = new FormatFactory();
        $formats = [];

        // Create all formats (except HTML)
        $formatNames = $formatFactory->getFormatNames();
        foreach ($formatNames as $formatName) {
            if ($formatName === 'Form') {
                continue;
            }
            // The format url is relative, but should be absolute in order to help feed readers.
            $formatUrl = '?' . str_ireplace('format=Form', 'format=' . $formatName, $queryString);
            $formatObject = $formatFactory->create($formatName);
            $formats[] = [
                'url'       => $formatUrl,
                'name'      => $formatName,
                'type'      => $formatObject->getMimeType(),
            ];
        }


        $donationUri = null;
        if (Configuration::getConfig('admin', 'donations') && $feedArray['donationUri']) {
            $donationUri = $feedArray['donationUri'];
        }

        $request = Request::fromGlobals();
        $bridgeName = $_GET['bridge'];
        $card = (new BridgeCard())->render($bridgeName, $request, true);
        return render(__DIR__ . '/../templates/form-format.html.php', [
            'bridgeForm' => $card,

            'charset'       => $this->getCharset(),
            'title'         => $feedArray['name'],
            'formats'       => $formats,
            'uri'           => $feedArray['uri'],
            'items'         => [],
            'donation_uri'  => $donationUri,
        ]);
    }
}
