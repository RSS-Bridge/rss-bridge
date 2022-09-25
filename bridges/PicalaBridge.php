<?php

class PicalaBridge extends BridgeAbstract
{
    const TYPES      = [
        'Actualités' => 'actualites',
        'Économie'   => 'economie',
        'Tests'      => 'tests',
        'Pratique'   => 'pratique',
    ];
    const NAME          = 'Picala Bridge';
    const URI           = 'https://www.picala.fr';
    const DESCRIPTION   = 'Dernière nouvelles du média indépendant sur le vélo électrique';
    const MAINTAINER    = 'Chouchen';
    const PARAMETERS    = [
        [
            'type' => [
                'name' => 'Type',
                'type' => 'list',
                'values' => self::TYPES,
            ],
        ],
    ];

    public function getURI()
    {
        if (!is_null($this->getInput('type'))) {
            return sprintf('%s/%s', static::URI, $this->getInput('type'));
        }

        return parent::getURI();
    }

    public function getIcon()
    {
        return 'https://picala-static.s3.amazonaws.com/static/img/favicon/favicon-32x32.png';
    }

    public function getDescription()
    {
        if (!is_null($this->getInput('type'))) {
            return sprintf('%s - %s', static::DESCRIPTION, array_search($this->getInput('type'), self::TYPES));
        }

        return parent::getDescription();
    }

    public function getName()
    {
        if (!is_null($this->getInput('type'))) {
            return sprintf('%s - %s', static::NAME, array_search($this->getInput('type'), self::TYPES));
        }

        return parent::getName();
    }

    public function collectData()
    {
        $fullhtml = getSimpleHTMLDOM($this->getURI());
        foreach ($fullhtml->find('.list-container-category a') as $article) {
            $srcsets = explode(',', $article->find('img', 0)->getAttribute('srcset'));
            $image = explode(' ', trim(array_shift($srcsets)))[0];

            $item = [];
            $item['uri'] = self::URI . $article->href;
            $item['title'] = $article->find('h2', 0)->plaintext;
            $item['content'] = sprintf(
                '<img src="%s" /><br>%s',
                $image,
                $article->find('.teaser__text', 0)->plaintext
            );
            $this->items[] = $item;
        }
    }
}
