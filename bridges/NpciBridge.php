<?php

class NpciBridge extends BridgeAbstract
{
    const MAINTAINER = 'captn3m0';
    const NAME = 'NCPI Circulars';
    const URI = 'https://npci.org.in';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'Returns circulars from National Payments Corporation of India)';

    const URL_SUFFIX = [
        'cts' => 'circulars',
        'upi' => 'circular',
        'rupay' => 'circulars',
        'nach' => 'circulars',
        'imps' => 'circular',
        'netc-fastag' => 'circulars',
        '99' => 'circular',
        'nfs' => 'circulars',
        'aeps' => 'circulars',
        'bhim-aadhaar' => 'circular',
        'e-rupi' => 'circular',
        'Bharat QR' => 'circulars',
        'bharat-billpay' => 'circulars',
    ];

    const PARAMETERS = [[
        'product' => [
            'name' => 'product',
            'type' => 'list',
            'values' => [
                'CTS' => 'cts',
                'UPI' => 'upi',
                'RuPay' => 'rupay',
                'NACH' => 'nach',
                'IMPS' => 'imps',
                'NETC FASTag' => 'netc-fastag',
                '*99#' => '99',
                'NFS' => 'nfs',
                'AePS' => 'aeps',
                'BHIM Aadhaar' => 'bhim-aadhaar',
                'e-RUPI' => 'e-rupi',
                'Bharat BillPay' => 'bharat-billpay'
            ]
        ]
    ]];

    public function getName()
    {
        $product = $this->getInput('product');
        if ($product) {
            $productNameMap = array_flip(self::PARAMETERS[0]['product']['values']);
            $productName = $productNameMap[$product];
            return "NPCI Circulars: $productName";
        }

        return 'NPCI Circulars';
    }

    public function getURI()
    {
        $product = $this->getInput('product');
        return $product ? sprintf('%s/what-we-do/%s/%s', self::URI, $product, self::URL_SUFFIX[$product]) : self::URI;
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI());
        $year = date('Y');
        $elements = $html->find("div[id=year$year] .pdf-item");

        foreach ($elements as $element) {
            $title = $element->find('p', 0)->innertext;

            $link = $element->find('a', 0);

            $uri = null;

            if ($link) {
                $pdfLink = $link->getAttribute('href');
                $uri = self::URI . str_replace(' ', '+', $pdfLink);
            }

            $item = [
                'uri' => $uri,
                'title' => $title,
                'content' => $title ,
                'uid' => sha1($pdfLink),
                'enclosures' => [
                    $uri
                ]
            ];

            $this->items[] = $item;
        }

        $this->items = array_slice($this->items, 0, 15);
    }
}
