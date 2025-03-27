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
                'name' => 'Profile',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '678459/Aziani-Studios',
                'title' => 'id/profile or url',
            ],
        ]
    ];

    private ?simple_html_dom $htmlDom = null;
    private ?string $parsedProfileInput = null;

    public function collectData()
    {
        $profile = $this->getInput('profile');
        if (!$profile) {
            throw new \Exception('No value for \'profile\' was provided.');
        }

        if (preg_match('#^(\d+/.*)$#', $profile, $m)) {
            $this->parsedProfileInput = $m[1];
        } elseif (preg_match('#https://(www.)?manyvids.com/Profile/(\d+/.*?)/#', $profile, $m)) {
            $this->parsedProfileInput = $m[2];
        } else {
            throw new \Exception(sprintf('Profile could not be parsed: %s', $profile));
        }

        $profileUrl = $this->getUri();
        $url = sprintf('%s?sort=newest', $profileUrl);
        $opt = [CURLOPT_COOKIE => 'sfwtoggle=false'];
        $this->htmlDom = getSimpleHTMLDOM($url, [], $opt);

        $elements = $this->htmlDom->find('div[class^="ProfileTabGrid_card__"]');

        foreach ($elements as $element) {
            $content = '';

            $title = $element->find('span[class^="VideoCardUI_videoTitle__"] > a', 0);
            if (!$title) {
                continue;
            }

            $linkElement = $element->find('a[href^="/Video/"]', 0);
            if ($linkElement) {
                $itemUri = self::URI . $linkElement->getAttribute('href');
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
                'content' => $content,
            ];
        }
    }

    public function getName()
    {
        if (!is_null($this->htmlDom)) {
            $profileNameElement = $this->htmlDom->find('[class^="ProfileAboutMeUI_stageName__"]', 0);
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
        if (!is_null($this->parsedProfileInput)) {
            return sprintf('%s/Profile/%s/Store/Videos', self::URI, $this->parsedProfileInput);
        }

        return parent::getUri();
    }
}
