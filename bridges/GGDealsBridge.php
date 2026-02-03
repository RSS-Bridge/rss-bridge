<?php

class GGDealsBridge extends BridgeAbstract
{
    const DESCRIPTION = 'Returns the price history for a game from gg.deals.';
    const MAINTAINER = 'phantop';
    const NAME = 'GG.deals';
    const URI = 'https://gg.deals/';

    const PARAMETERS = [[
        'slug' => [
            'name' => 'Game slug',
            'type' => 'text',
            'required' => true,
            'title' => 'Game slug from the gg.deals URL',
            'exampleValue' => 'a-hat-in-time-ultimate-edition-nintendo-switch'
        ],
        'region' => [
            'name' => 'Region',
            'type' => 'list',
            'title' => 'Select the region for pricing',
            'defaultValue' => 'us',
            'values' => [
                'Australia' => 'au',
                'Belgium' => 'be',
                'Brazil' => 'br',
                'Canada' => 'ca',
                'Denmark' => 'dk',
                'Europe' => 'eu',
                'Finland' => 'fi',
                'France' => 'fr',
                'Germany' => 'de',
                'Ireland' => 'ie',
                'Italy' => 'it',
                'Netherlands' => 'nl',
                'Norway' => 'no',
                'Poland' => 'pl',
                'Spain' => 'es',
                'Sweden' => 'se',
                'Switzerland' => 'ch',
                'United Kingdom' => 'gb',
                'United States' => 'us',
            ],
        ],
        'keyshops' => [
            'name' => 'Include keyshops',
            'type' => 'checkbox',
            'title' => 'Check to include prices from keyshops',
            'defaultValue' => 'checked'
        ],
        'lowest' => [
            'name' => 'Only return lowest prices',
            'type' => 'checkbox',
            'title' => 'Check to only show a price if it\'s the new lowest',
            'defaultValue' => 'checked'
        ],
    ]];

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI());
        $html = defaultLinkTo($html, self::URI);
        $types = ['official-stores'];
        if ($this->getInput('keyshops')) {
            $types[] = 'keyshops';
        }

        foreach ($types as $type) {
            $low = false;
            foreach ($html->find("#$type .game-item") as $deal) {
                $item = [
                    'author' => $deal->getAttribute('data-shop-name'),
                    'categories' => [ $deal->find('.tag-drm svg, time', 0)->getAttribute('title'),
                                      $deal->find('.label.historical', 0)->plaintext,
                                      $deal->find('.label.best', 0)->plaintext,
                                      $deal->find('.code', 0)->plaintext,
                                      $type ],
                    'timestamp' => $deal->find('time', 0)->getAttribute('datetime'),
                    'title' => $deal->find('.price-inner, .price-text', 0)->plaintext,
                    'uri' => $deal->find('.full-link', 0)->href,
                ];
                // Unsure how referral links change—exclude from guid
                $item['uid'] = implode('', array_diff_key($item, ['url' => '']));


                // First entry for type is always the lowest
                if (!$low || $item['title'] = $low) {
                    $low = $item['title'];
                    $item['title'] .= " ($type low)";
                    $item['categories'][] = 'Low Price';
                }

                $this->items[] = $item;

                // First entry for type is always the lowest
                if ($this->getInput('lowest')) {
                    break;
                }
            }
        }
    }

    public function getName()
    {
        $name = parent::getName();
        if ($this->getInput('slug')) {
            $html = getSimpleHTMLDOMCached($this->getURI());
            $name .= ' - ' . end($html->find('[itemscope] span'))->innertext;
        }
        return $name;
    }

    public function getURI()
    {
        $uri = parent::getURI();
        if ($this->getInput('slug')) {
            $uri .= $this->getInput('region') . '/game/' . $this->getInput('slug');
        }
        return $uri;
    }
}
