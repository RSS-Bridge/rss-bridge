<?php

class OnVaSortirBridge extends FeedExpander
{
    const MAINTAINER = 'AntoineTurmel';
    const NAME = 'OnVaSortir';
    const URI = 'https://www.onvasortir.com';
    const DESCRIPTION = 'Returns the newest events from OnVaSortir (full text)';
    const PARAMETERS = [
            [
            'city' => [
                'name' => 'City',
                'type' => 'list',
                'values' => [
                    'Agen' => 'Agen',
                    'Ajaccio' => 'Ajaccio',
                    'Albi' => 'Albi',
                    'Amiens' => 'Amiens',
                    'Angers' => 'Angers',
                    'Angoulême' => 'Angouleme',
                    'Annecy' => 'annecy',
                    'Aurillac' => 'aurillac',
                    'Auxerre' => 'auxerre',
                    'Avignon' => 'avignon',
                    'Béziers' => 'Beziers',
                    'Bastia' => 'Bastia',
                    'Beauvais' => 'Beauvais',
                    'Belfort' => 'Belfort',
                    'Bergerac' => 'bergerac',
                    'Besançon' => 'Besancon',
                    'Biarritz' => 'Biarritz',
                    'Blois' => 'Blois',
                    'Bordeaux' => 'bordeaux',
                    'Bourg-en-Bresse' => 'bourg-en-bresse',
                    'Bourges' => 'Bourges',
                    'Brest' => 'Brest',
                    'Brive' => 'brive-la-gaillarde',
                    'Bruxelles' => 'bruxelles',
                    'Caen' => 'Caen',
                    'Calais' => 'Calais',
                    'Carcassonne' => 'Carcassonne',
                    'Châteauroux' => 'Chateauroux',
                    'Chalon-sur-saone' => 'chalon-sur-saone',
                    'Chambéry' => 'chambery',
                    'Chantilly' => 'chantilly',
                    'Charleroi' => 'charleroi',
                    'Charleville-Mézières' => 'Charleville-Mezieres',
                    'Chartres' => 'Chartres',
                    'Cherbourg' => 'Cherbourg',
                    'Cholet' => 'cholet',
                    'Clermont-Ferrand' => 'Clermont-Ferrand',
                    'Compiègne' => 'compiegne',
                    'Dieppe' => 'dieppe',
                    'Dijon' => 'Dijon',
                    'Dunkerque' => 'Dunkerque',
                    'Evreux' => 'evreux',
                    'Fréjus' => 'frejus',
                    'Gap' => 'gap',
                    'Genève' => 'geneve',
                    'Grenoble' => 'Grenoble',
                    'La Roche sur Yon' => 'La-Roche-sur-Yon',
                    'La Rochelle' => 'La-Rochelle',
                    'Lausanne' => 'lausanne',
                    'Laval' => 'Laval',
                    'Le Havre' => 'le-havre',
                    'Le Mans' => 'le-mans',
                    'Liège' => 'liege',
                    'Lille' => 'lille',
                    'Limoges' => 'Limoges',
                    'Lorient' => 'Lorient',
                    'Luxembourg' => 'Luxembourg',
                    'Lyon' => 'lyon',
                    'Marseille' => 'marseille',
                    'Metz' => 'Metz',
                    'Mons' => 'Mons',
                    'Mont de Marsan' => 'mont-de-marsan',
                    'Montauban' => 'Montauban',
                    'Montluçon' => 'montlucon',
                    'Montpellier' => 'montpellier',
                    'Mulhouse' => 'Mulhouse',
                    'Nîmes' => 'nimes',
                    'Namur' => 'Namur',
                    'Nancy' => 'Nancy',
                    'Nantes' => 'nantes',
                    'Nevers' => 'nevers',
                    'Nice' => 'nice',
                    'Niort' => 'niort',
                    'Orléans' => 'orleans',
                    'Périgueux' => 'perigueux',
                    'Paris' => 'paris',
                    'Pau' => 'Pau',
                    'Perpignan' => 'Perpignan',
                    'Poitiers' => 'Poitiers',
                    'Quimper' => 'Quimper',
                    'Reims' => 'Reims',
                    'Rennes' => 'Rennes',
                    'Roanne' => 'roanne',
                    'Rodez' => 'rodez',
                    'Rouen' => 'Rouen',
                    'Saint-Brieuc' => 'Saint-Brieuc',
                    'Saint-Etienne' => 'saint-etienne',
                    'Saint-Malo' => 'saint-malo',
                    'Saint-Nazaire' => 'saint-nazaire',
                    'Saint-Quentin' => 'saint-quentin',
                    'Saintes' => 'saintes',
                    'Strasbourg' => 'Strasbourg',
                    'Tarbes' => 'Tarbes',
                    'Toulon' => 'Toulon',
                    'Toulouse' => 'Toulouse',
                    'Tours' => 'Tours',
                    'Troyes' => 'troyes',
                    'Valence' => 'valence',
                    'Vannes' => 'vannes',
                    'Zurich' => 'zurich',
                ]
            ]
            ]
    ];

    protected function parseItem($item)
    {
        $item = parent::parseItem($item);
        $html = getSimpleHTMLDOMCached($item['uri']);
        $text = $html->find('div.corpsMax', 0)->innertext;
        $item['content'] = utf8_encode($text);
        return $item;
    }

    public function collectData()
    {
        $this->collectExpandableDatas('https://' .
            $this->getInput('city') . '.onvasortir.com/rss.php');
    }
}
