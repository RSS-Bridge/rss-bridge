<?php

class PokemonTVBridge extends BridgeAbstract
{
    const NAME = 'PokemonTV Bridge';
    const URI = 'https://www.pokemon.com/';
    const DESCRIPTION = 'Returns latest episodes from PokemonTV';
    const MAINTAINER = 'Bockiii';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [ [
        'language' => [
            'name' => 'Language',
            'type' => 'list',
            'title' => 'Select your language',
            'values' => [
                'Danish' => 'dk',
                'Dutch' => 'nl',
                'English (UK)' => 'uk',
                'English (US)' => 'us',
                'Finish' => 'fi',
                'French' => 'fr',
                'German' => 'de',
                'Italian' => 'it',
                'Latin America' => 'el',
                'Norwegian' => 'no',
                'Portoguese' => 'br',
                'Russian' => 'ru',
                'Spanish' => 'es',
                'Swedish' => 'se'
            ],
            'defaultValue' => 'English (US)'
        ],
        'filtername' => [
            'name' => 'Series Name Filter',
            'exampleValue' => 'Ultra',
            'required' => false
        ],
        'filterseason' => [
            'name' => 'Series Season Filter',
            'exampleValue' => '22',
            'required' => false
        ]
    ]];

    public function collectData()
    {
        $link = 'https://www.pokemon.com/api/pokemontv/v2/channels/' . $this->getInput('language');

        $html = getSimpleHTMLDOM($link);
        $parsed_json = json_decode($html);

        $filtername = $this->getInput('filtername');
        $filterseason = $this->getInput('filterseason');

        foreach ($parsed_json as $element) {
            if (strlen($filtername) >= 1) {
                if (!(stristr($element->{'channel_name'}, $filtername) !== false)) {
                    continue;
                }
            }
            foreach ($element->{'media'} as $mediaelement) {
                if (strlen($filterseason) >= 1) {
                    if ($mediaelement->{'season'} != $filterseason) {
                        continue;
                    }
                }
                switch ($element->media_type) {
                    case 'movie':
                    case 'junior':
                    case 'original':
                    case 'non-animation':
                        $itemtitle = $element->channel_name;
                        break;
                    case 'episode':
                        $season = str_pad($mediaelement->{'season'}, 2, '0', STR_PAD_LEFT);
                        $episode = str_pad($mediaelement->{'episode'}, 2, '0', STR_PAD_LEFT);
                        $itemtitle = $element->{'channel_name'} . ' - S' . $season . 'E' . $episode;
                        break;
                    default:
                        $itemtitle = '';
                }
                $streamurl = 'https://watch.pokemon.com/' . $this->getCountryCode() . '/#/player?id=' . $mediaelement->{'id'};
                $item = [];
                $item['uri'] = $streamurl;
                $item['title'] = $itemtitle;
                $item['timestamp'] = $mediaelement->{'last_modified'};
                $item['content'] = '<h1>' . $itemtitle . ' ' . $mediaelement->{'title'}
                    . '</h1><br><br><a href="'
                    . $streamurl
                    . '"><img src="'
                    . $mediaelement->{'images'}->{'medium'}
                    . '" /></a><br><br>'
                    . $mediaelement->{'description'}
                    . '<br><br><a href="' . $mediaelement->{'offline_url'} . '">Download</a>';
                $this->items[] = $item;
            }
        }
    }

    private function getCountryCode()
    {
        switch ($this->getInput('language')) {
            case 'us':
                return 'en-us';
                break;
            case 'de':
                return 'de-de';
                break;
            case 'fr':
                return 'fr-fr';
                break;
            case 'es':
                return 'es-es';
                break;
            case 'el':
                return 'es-xl';
                break;
            case 'it':
                return 'it-it';
                break;
            case 'dk':
                return 'da-dk';
                break;
            case 'fi':
                return 'fi-fi';
                break;
            case 'br':
                return 'pt-br';
                break;
            case 'uk':
                return 'en-gb';
                break;
            case 'ru':
                return 'ru-ru';
                break;
            case 'nl':
                return 'nl-nl';
                break;
            case 'no':
                return 'nb-no';
                break;
            case 'se':
                return 'sv-se';
                break;
        }
    }

    public function getIcon()
    {
        return 'https://assets.pokemon.com/static2/_ui/img/favicon.ico';
    }
}
