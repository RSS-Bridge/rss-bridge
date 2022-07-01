<?php

class NikonDownloadCenterBridge extends BridgeAbstract
{
    const NAME = 'Nikon Download Center – What\'s New';
    const URI = 'https://downloadcenter.nikonimglib.com/';
    const DESCRIPTION = 'Firmware updates and new software from Nikon.';
    const MAINTAINER = 'sal0max';
    const CACHE_TIMEOUT = 60 * 60 * 2; // 2 hours

    public function getURI()
    {
        $year = date('Y');
        return self::URI . 'en/update/index/' . $year . '.html';
    }

    public function getIcon()
    {
        return self::URI . 'favicon.ico';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('dd>ul>li') as $element) {
            $date        = $element->find('.date', 0)->plaintext;
            $productType = $element->find('.icon>img', 0)->alt;
            $desc        = $element->find('p>a', 0)->plaintext;
            $link        = urljoin(self::URI, $element->find('p>a', 0)->href);

            $item = [
                'title'     => $desc,
                'uri'       => $link,
                'timestamp' => strtotime($date),
                'content'   => <<<EOD
<p>
 New/updated {$productType}:<br>
 <strong><a href="{$link}">{$desc}</a></strong>
</p>
<p>
 {$date}
</p>
EOD
            ];
            $this->items[] = $item;
        }
    }
}
