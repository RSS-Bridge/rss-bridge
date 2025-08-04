<?php

class SubitoBridge extends BridgeAbstract
{
    const NAME = 'Subito';
    const URI = 'https://www.subito.it/';
    const DESCRIPTION = 'Returns ads from search';
    const MAINTAINER = 'bagnacauda';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [[
        'url' => [
            'name' => 'Search URL',
            'title' => 'The URL from your browser\'s address bar after searching and filtering',
            'exampleValue' => 'https://www.subito.it/annunci-lombardia/vendita/elettronica/milano/milano/?q=iphone',
            'required' => true
        ],
        'hideSoldItems' => [
            'name' => 'Hide sold items',
            'title' => 'Hide ads of recently sold items (which are normally displayed for a while)',
            'type' => 'checkbox',
            'defaultValue' => false,
        ],
    ]];

    public function collectData()
    {
        $url = $this->getInput('url');

        $dom = getSimpleHTMLDOMCached($url);

        $json = $dom->getElementById('__NEXT_DATA__');
        $data = json_decode($json->innertext());

        foreach ($data->props->pageProps->initialState->items->list as $post) {
            $post = $post->item;

            $item = [];
            $item['uri'] = $post->urls->default;
            $item['title'] = $post->subject;
            $item['timestamp'] = $post->date;
            $item['enclosures'] = [];
            $item['content'] = '';
            $skip_item = false;

            $features_html = [];
            $price_html = '';
            foreach ($post->features as $key => $feature) {
                $html = $feature->label . ': ';
                $skip_feature = false;

                foreach ($feature->values as $value) {
                    if ($this->getInput('hideSoldItems') && $feature->uri == '/transaction_status' && $value->value == 'SOLD') {
                        $skip_item = true;
                        break;
                    }

                    if ($feature->uri == '/price') {
                        $price_html = '<h2>' . $value->value . '</h2>';
                        $skip_feature = true;
                    }

                    $html .= $value->value . ' ';
                }

                if (!$skip_feature) {
                    $html .= '<br>';
                    $features_html[] = $html;
                }
            }

            if ($skip_item) {
                continue;
            }

            $query_img = '';
            foreach ($dom->find('script[type=application/ld+json]') as $json) {
                $ld_json = json_decode($json->innertext());
                if (property_exists($ld_json, '@graph') && count($ld_json->{'@graph'}) > 0 && property_exists($ld_json->{'@graph'}[0], 'contentUrl')) {
                    $query_img = explode('?', $ld_json->{'@graph'}[0]->contentUrl)[1]; // i pick the first query string, to use for all images
                    break;
                }
            }

            foreach ($post->images as $image) {
                $item['enclosures'][] = $image->cdnBaseUrl . '?' . $query_img;
            }

            if (count($item['enclosures']) > 0) {
                $item['content'] = '<img src="' . $item['enclosures'][0] . '"><br>';
            }

            $item['content'] .= $price_html;
            $item['content'] .= $post->geo->town->value . ' (' . $post->geo->city->shortName . ')<br><br>';

            sort($features_html);
            $item['content'] .= implode($features_html);

            $item['content'] .= '<br>';

            $item['content'] .= str_replace("\n", '<br>', $post->body);

            $this->items[] = $item;
        }
    }
}
