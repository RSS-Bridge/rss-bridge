<?php

class TheFarSideBridge extends BridgeAbstract
{
    const NAME = 'The Far Side Bridge';
    const URI = 'https://www.thefarside.com';
    const DESCRIPTION = 'Returns the daily dose';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [];

    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        $div = $html->find('div.tfs-page-container__cows', 0);

        $item = [];
        $item['uri'] = $html->find('meta[property="og:url"]', 0)->content;
        $item['title'] = $div->find('h3', 0)->innertext;
        $item['timestamp'] = $div->find('h3', 0)->innertext;
        $item['content'] = '';

        foreach ($div->find('div.card-body') as $index => $card) {
            $image = $card->find('img', 0);
            $imageUrl = $image->attr['data-src'];

            // Images are downloaded to bypass the hotlink protection.
            $image = getContents($imageUrl, ['Referer: ' . self::URI]);

            // Encode image as base64
            $imageBase64 = base64_encode($image);

            $caption = '';

            if ($card->find('figcaption', 0)) {
                $caption = $card->find('figcaption', 0)->innertext;
            }

            $item['content'] .= <<<EOD
<figure>
	<img title="{$caption}" src="data:image/jpeg;base64,{$imageBase64}"/>
	<figcaption>{$caption}</figcaption>
</figure>
<br/>
EOD;
        }

        $this->items[] = $item;
    }
}
