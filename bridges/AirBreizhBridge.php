<?php

class AirBreizhBridge extends BridgeAbstract
{
    const MAINTAINER = 'fanch317';
    const NAME = 'Air Breizh';
    const URI = 'https://www.airbreizh.asso.fr/';
    const DESCRIPTION = 'Returns newests publications on Air Breizh';
    const PARAMETERS = [
        'Publications' => [
            'theme' => [
                'name' => 'Thematique',
                'type' => 'list',
                'values' => [
                    'Tout' => '',
                    'Rapport d\'activite' => 'rapport-dactivite',
                    'Etude' => 'etudes',
                    'Information' => 'information',
                    'Autres documents' => 'autres-documents',
                    'Plan Régional de Surveillance de la qualité de l’air' => 'prsqa',
                    'Transport' => 'transport'
                ]
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://www.airbreizh.asso.fr/voy_content/uploads/2017/11/favicon.png';
    }

    public function collectData()
    {
        $html = '';
        $html = getSimpleHTMLDOM(static::URI . 'publications/?fwp_publications_thematiques=' . $this->getInput('theme'));

        foreach ($html->find('article') as $article) {
            $item = [];
            // Title
            $item['title'] = $article->find('h2', 0)->plaintext;
            // Author
            $item['author'] = 'Air Breizh';
            // Image
            $imagelink = $article->find('.card__image', 0)->find('img', 0)->getAttribute('src');
            // Content preview
            $item['content'] = '<img src="' . $imagelink . '" />
			<br/>'
            . $article->find('.card__text', 0)->plaintext;
            // URL
            $item['uri'] = $article->find('.publi__buttons', 0)->find('a', 0)->getAttribute('href');
            // ID
            $item['id'] = $article->find('.publi__buttons', 0)->find('a', 0)->getAttribute('href');
            $this->items[] = $item;
        }
    }
}
