<?php

class ManyVidsBridge extends BridgeAbstract
{
    const NAME = 'ManyVids';
    const URI = 'https://www.manyvids.com';
    const DESCRIPTION = 'Fetches the latest posts from a profile';
    const MAINTAINER = 'dvikan, subtle4553';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        [
            'profile' => [
                'name' => 'Profil',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '678459/Aziani-Studios',
                'title' => 'id/profile or url',
            ],
        ]
    ];

    private $domCache = null;

    public function collectData()
    {
        $profile = $this->getInput('profile');
        if (preg_match('#^(\d+/.*)$#', $profile, $m)) {
            $profile = $m[1];
        } elseif (preg_match('#https://www.manyvids.com/Profile/(\d+/\w+)#', $profile, $m)) {
            $profile = $m[1];
        } else {
            throw new \Exception('nope');
        }

        $dom = $this->getHTML($profile);
        $elements = $dom->find('div[class^="ProfileTabGrid_card__"]');

        foreach ($elements as $element) {
            $content = '';

            $title = $element->find('span[class^="VideoCardUI_videoTitle__"] > a', 0);
            if (!$title) {
                continue;
            }

            $linkElement = $element->find('a[href^="/Video/"]', 0);
            if ($linkElement) {
                $itemUri = $this::URI . $linkElement->getAttribute('href');
            }

            $image = $element->find('img', 0);
            if ($image) {
                if (isset($itemUri)) {
                    $content .= sprintf('<p><a href="%s"><img src="%s"></a></p>', $itemUri, $image->getAttribute('src'));
                } else {
                    $content .= sprintf('<p><img src="%s"></p>', $image->getAttribute('src'));
                }
            }

            $contentSegments = [];

            $videoLength = $element->find('[class^="CardMedia_videoDuration__"] > span', 0);
            if ($videoLength) {
                $contentSegments[] = sprintf('%s', $videoLength->innertext);
            }

            $price = $element->find('[class^="PriceUI_regularPrice__"], [class^="PriceUI_card_price__"] > p, [class^="PriceUI_card_free_text__"]', 0);
            $discountedPrice = $element->find('[class^="PriceUI_discountedPrice__"]', 0);

            if ($price && $discountedPrice) {
                $contentSegments[] = sprintf('<s>%s</s> <strong>%s</strong>', $price->innertext, $discountedPrice->innertext);
            } elseif ($price && !$discountedPrice) {
                $contentSegments[] = sprintf('<strong>%s</strong>', $price->innertext);
            }

            $content .= implode(' • ', $contentSegments);

            $this->items[] = [
                'title' => $title->innertext,
                'uri' => isset($itemUri) ? $itemUri : null,
                'content' => $content
            ];
        }
    }

    public function getName()
    {
        $profile = $this->getInput('profile');

        if ($profile) {
            $dom = $this->getHTML($profile);
            $profileNameElement = $dom->find('[class^="ProfileAboutMeUI_stageName__"]', 0);
            if (!$profileNameElement) {
                return parent::getName();
            }

            $profileNameElementContent = $profileNameElement->innertext;
            $index = strpos($profileNameElementContent, '<');
            $profileName = substr($profileNameElementContent, 0, $index);

            return 'ManyVids: ' . $profileName;
        }

        return parent::getName();
    }

    public function getUri()
    {
        $profile = $this->getInput('profile');
        if ($profile) {
            return sprintf('%s/Profile/%s/Store/Videos', $this::URI, $profile);
        }

        return parent::getUri();
    }

    private function getHTML($profile)
    {
        if (is_null($this->domCache)) {
            $opt = [CURLOPT_COOKIE => 'sfwtoggle=false'];
            $url = sprintf('https://manyvids.com/Profile/%s/Store/Videos?sort=newest', $profile);
            $this->domCache = getSimpleHTMLDOM($url, [], $opt);
        }

        return $this->domCache;
    }
}
