<?php

/**
 * A bridge allowing fetching of Radio France radios transcripts.
 * I expect it to work at least for France Inter and France Culture.
 * I currently test it with
 * * Burne Out (https://www.radiofrance.fr/franceinter/podcasts/burne-out)
 * * La méthode scientifique (https://www.radiofrance.fr/franceculture/podcasts/la-methode-scientifique)
 * * Las science CQFD
 */
class RadioFranceBridge extends BridgeAbstract
{
    const NAME          = 'Radio France';
    const URI           = 'https://www.radiofrance.fr/franceinter/podcasts';
    const DESCRIPTION   = 'A bridge allowing to read transcripts for Radio France shows';
    const MAINTAINER    = 'Riduidel';
    const DEFAULT_DOMAIN = 'https://www.radiofrance.fr';

    /*
     * The URL Prefix of the (Webapp-)API
     * @const APIENDPOINT https-URL of the used endpoint
     */
    const APIENDPOINT = 'https://www.radiofrance.fr/api/v2.0/path';
    const PARAMETERS = [
        [
        'domain' => [
            'name' => 'Domain to use',
            'required' => true,
            'defaultValue' => self::DEFAULT_DOMAIN
        ],
        'page' => [
            'name' => 'Initial page to load',
            'required' => true,
            'exampleValue' => 'franceinter/podcasts/burne-out'
        ]
        ]];

    private function getDomain()
    {
        $domain = $this->getInput('domain');
        if (empty($domain)) {
            $domain = self::DEFAULT_DOMAIN;
        }
        if (strpos($domain, '://') === false) {
            $domain = 'https://' . $domain;
        }
        return $domain;
    }

    public function getURI()
    {
        return $this->getDomain() . '/' . $this->getInput('page');
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        // An array of dom nodes
        $documentsList = $html->find('.DocumentsList', 0);
        $documentsListWrapper = $documentsList->find('.DocumentsList-wrapper', 0);
        $cardList = $documentsListWrapper->find('.CardMedia');

        foreach ($cardList as $card) {
            $item = [];
            $title_link = $card->find('.ConceptTitle a', 0);
            $item['title'] = $title_link->plaintext;
            $uri = $title_link->getAttribute('href', 0);
            switch (substr($uri, 0, 1)) {
                case 'h': // absolute uri
                    $item['uri'] = $uri;
                    break;
                case '/': // domain relative uri
                    $item['uri'] = $this->getDomain() . $uri;
                    break;
                default:
                    $item['uri'] = $this->getDomain() . '/' . $uri;
            }
            // Finally, obtain the mp3 from some weird Radio France API (url obtained by reading network calls, no less)
            $media_url = self::APIENDPOINT . '?value=' . $uri;
            $rawJSON = getSimpleHTMLDOMCached($media_url);
            $processedJSON = json_decode($rawJSON);
            $model_content = $processedJSON->content;
            if (empty($model_content->manifestations)) {
                error_log("Seems like $uri has no manifestation");
            } else {
                $item['enclosures'] = [ $model_content->manifestations[0]->url ];

                $item['content'] = '';
                if (isset($model_content->visual)) {
                    $item['content'] .= "<img 
                        src=\"{$model_content->visual->src}\" 
                        alt=\"{$model_content->visual->legend}\"
                        style=\"float:left; width:400px; margin: 1em;\"/>";
                }
                if (isset($model_content->standFirst)) {
                    $item['content'] .= $model_content->standFirst;
                }
                if (isset($model_content->bodyJson)) {
                    if (!empty($item['content'])) {
                        $item['content'] .= '<hr/>';
                    }
                    $pseudo_html_array = array_map([$this, 'convertJsonElementToHTML'], $model_content->bodyJson);
                    $pseudo_html_text = array_reduce(
                        $pseudo_html_array,
                        function ($text, $element) {
                            return $text . "\n" . $element;
                        },
                        ''
                    );
                    $item['content'] .= $pseudo_html_text;
                }
                if (isset($model_content->producers)) {
                    $item['author'] = $this->readAuthorsNamesFrom($model_content->producers);
                } elseif (isset($model_content->staff)) {
                    $item['author'] = $this->readAuthorsNamesFrom($model_content->staff);
                }
                $time = $card->find('time', 0);
                $timevalue = $time->getAttribute('datetime');
                $item['timestamp'] = strtotime($timevalue);

                $this->items[] = $item;
            }
        }
    }

    private function readAuthorsNamesFrom($persons_array)
    {
        $persons_names = array_map(function ($person_element) {
            return $person_element->name;
        }, $persons_array);
        return array_reduce($persons_names, function ($a, $b) {
            if (!empty($a)) {
                $a .= ', ';
            }
            return $a . $b;
        }, '');
    }

    private function convertJsonElementToHTML($jsonElement)
    {
        $childText = isset($jsonElement->children) ? $this->convertJsonChildrenToHTML($jsonElement->children) : '';
        $valueText = isset($jsonElement->value) ? $jsonElement->value : '';
        switch ($jsonElement->type) {
            case 'text':
                return "{$childText}{$valueText}";
            case 'heading':
                $level = $jsonElement->level;
                return "<h$level>{$childText}{$valueText}</h$level>";
            case 'list':
                $tag = 'ul';
                if (isset($jsonElement->ordered)) {
                    if ($jsonElement->ordered) {
                        $tag = 'ol';
                    }
                }
                return "<$tag>\n" . $childText . "</$tag>\n";
            case 'list_item':
                return "<li>{$childText}{$valueText}</li>\n";
            case 'bounce':
                return '';
            case 'paragraph':
                return "<p>{$childText}{$valueText}</p>\n";
            case 'quote':
                return "<blockquote>{$childText}{$valueText}</blockquote>\n";
            case 'link':
                return "<a href=\"{$jsonElement->data->href}\">{$childText}{$valueText}</a>\n";
            case 'audio':
                return '';
            case 'embed':
                return $jsonElement->data->html;
            default:
                return $jsonElement->value;
        }
    }

    private function convertJsonChildrenToHTML($children)
    {
        $converted = array_map([$this, 'convertJsonElementToHTML'], $children);
        return array_reduce($converted, function ($a, $b) {
            return $a . $b;
        }, '');
    }

    private function removeAds($element)
    {
        $ads = $element->find('AdSlot');
        foreach ($ads as $ad) {
            $ad->remove();
        }
        return $element;
    }

    /**
     * Replaces all relative URIs with absolute ones
     * @param $element A simplehtmldom element
     * @return The $element->innertext with all URIs replaced
     */
    private function replaceUriInHtmlElement($element)
    {
        $returned = $element->innertext;
        foreach (self::REPLACED_ATTRIBUTES as $initial => $final) {
            $returned = str_replace($initial . '="/', $final . '="' . self::URI . '/', $returned);
        }
        return $returned;
    }
}
