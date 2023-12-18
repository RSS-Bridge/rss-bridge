<?php

class PanneauPocketBridge extends BridgeAbstract
{
    const NAME = 'Panneau Pocket';
    const URI = 'https://app.panneaupocket.com';
    const DESCRIPTION = 'Fetches the latest infos from Panneau Pocket';
    const MAINTAINER = 'floviolleau';
    const CACHE_TIMEOUT = 7200; // 2h

    private static ?array $CITIES = null;

    public function __construct($cache, $logger)
    {
        parent::__construct($cache, $logger);

        // Assign the dynamic value to the constant in the constructor
        self::$CITIES = self::getCities();
    }

    public function collectData()
    {
        $matchedCity = array_search($this->getInput('city'), self::$CITIES);
        $city = strtolower($this->getInput('city') . '-' . $matchedCity);
        $url = sprintf('https://app.panneaupocket.com/ville/%s', urlencode($city));

        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('.sign-carousel--item') as $itemDom) {
            $item = [];

            $item['uri'] = $itemDom->find('button[type=button]', 0)->href;
            $item['title'] = $itemDom->find('.sign-preview__content .title', 0)->innertext;
            $item['author'] = 'floviolleau';
            $item['content'] = $itemDom->find('.sign-preview__content .content', 0)->innertext;

            $timestamp = $itemDom->find('span.date', 0)->plaintext;
            if (preg_match('#(?<d>[0-9]+)/(?<m>[0-9]+)/(?<y>[0-9]+)#', $timestamp, $match)) {
                $item['timestamp'] = "{$match['y']}-{$match['m']}-{$match['d']}";
            }

            $this->items[] = $item;
        }
    }

    /**
     * /!\ Warning
     * Display all cities in a select on the front-end can be time-consuming to render
     *
     * @return array
     * @throws CloudFlareException
     * @throws HttpException
     * @throws JsonException
     */
    private static function getCities(): array
    {
        $cities = json_decode(getContents(self::URI . '/public-api/city'), true, 512, JSON_THROW_ON_ERROR);

        $formattedCities = null;
        $maxTextSize = 50;
        foreach ($cities as $city) {
            $value = $city['name'] . ' - ' . $city['postCode'];

            // reduce length for very long cities' name else style page is broken
            // because of a too long value in select option.
            if (strlen($city['name']) > $maxTextSize) {
                // remove 11 char:
                //  '...' + ' - ' + postcode (5)
                $lastPos = ($maxTextSize - 11) - strlen($value);
                $value = substr($value, 0, strrpos($value, ' ', $lastPos)) . '... - ' . $city['postCode'];
            }
            $formattedCities[$value] = $city['id'];
        }

        return $formattedCities;
    }

    /**
     * override base method
     * @return array[]
     */
    public function getParameters(): array
    {
        return [
            [
                'city' => [
                    'name' => 'Choisir une ville',
                    'type' => 'list',
                    'values' => self::$CITIES,
                ]
            ]
        ];
    }

    /**
     * Override base method because they use static::PARAMETERS instead of getParameters
     * in function setInputWithContext
     *
     * else $this->getInput do not return any value
     *
     * @param array $input
     * @return void
     * @throws Exception
     */
    public function setInput(array $input)
    {
        parent::setInput($input);
        $this->setInputWithContext($input, $this->queriedContext);
    }

    /**
     * Override base method because they use static::PARAMETERS instead of getParameters
     *
     * else $this->getInput do not return any value
     *
     * @param array $input
     * @param $queriedContext
     * @return void
     */
    public function setInputWithContext(array $input, $queriedContext)
    {
        // Import and assign all inputs to their context
        foreach ($input as $name => $value) {
            foreach ($this->getParameters() as $context => $set) {
                if (array_key_exists($name, $this->getParameters()[$context])) {
                    $this->inputs[$context][$name]['value'] = $value;
                }
            }
        }

        // Apply default values to missing data
        $contexts = [$queriedContext];
        if (array_key_exists('global', $this->getParameters())) {
            $contexts[] = 'global';
        }

        foreach ($contexts as $context) {
            if (!isset($this->getParameters()[$context])) {
                // unknown context provided by client, throw exception here? or continue?
            }

            foreach ($this->getParameters()[$context] as $name => $properties) {
                if (isset($this->inputs[$context][$name]['value'])) {
                    continue;
                }

                $type = $properties['type'] ?? 'text';

                switch ($type) {
                    case 'checkbox':
                        $this->inputs[$context][$name]['value'] = $input[$context][$name]['value'] ?? false;
                        break;
                    case 'list':
                        if (!isset($properties['defaultValue'])) {
                            $firstItem = reset($properties['values']);
                            if (is_array($firstItem)) {
                                $firstItem = reset($firstItem);
                            }
                            $this->inputs[$context][$name]['value'] = $firstItem;
                        } else {
                            $this->inputs[$context][$name]['value'] = $properties['defaultValue'];
                        }
                        break;
                    default:
                        if (isset($properties['defaultValue'])) {
                            $this->inputs[$context][$name]['value'] = $properties['defaultValue'];
                        }
                        break;
                }
            }
        }

        // Copy global parameter values to the guessed context
        if (array_key_exists('global', $this->getParameters())) {
            foreach ($this->getParameters()['global'] as $name => $properties) {
                if (isset($input[$name])) {
                    $value = $input[$name];
                } else {
                    if ($properties['type'] ?? null === 'checkbox') {
                        $value = false;
                    } elseif (isset($properties['defaultValue'])) {
                        $value = $properties['defaultValue'];
                    } else {
                        continue;
                    }
                }
                $this->inputs[$queriedContext][$name]['value'] = $value;
            }
        }

        // Only keep guessed context parameters values
        if (isset($this->inputs[$queriedContext])) {
            $this->inputs = [$queriedContext => $this->inputs[$queriedContext]];
        } else {
            $this->inputs = [];
        }
    }
}

