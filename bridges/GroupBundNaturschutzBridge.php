<?php

class GroupBundNaturschutzBridge extends XPathAbstract
{
    const NAME = 'BUND Naturschutz in Bayern e.V. - Kreisgruppen';
    const URI = 'https://www.bund-naturschutz.de/ueber-uns/organisation/kreisgruppen-ortsgruppen';
    const DESCRIPTION = 'Returns the latest news from specified BUND Naturschutz in Bayern e.V. local group (Germany)';
    const MAINTAINER = 'dweipert';

    const PARAMETERS = [
        [
            'group' => [
                'name' => 'Group',
                'type' => 'list',
                'values' => [
                    // 'Aichach-Friedberg' => 'bn-aic.de', # non-uniform page
                    'Altötting' => 'altoetting',
                    'Amberg-Sulzbach' => 'amberg-sulzbach',
                    'Ansbach' => 'ansbach',
                    'Aschaffenburg' => 'aschaffenburg',
                    'Augsburg' => 'augsburg',
                    'Bad Kissingen' => 'bad-kissingen',
                    'Bad Tölz' => 'bad-toelz',
                    'Bamberg' => 'bamberg',
                    'Bayreuth' => 'bayreuth', # single entry # different layout
                    'Berchtesgadener Land' => 'berchtesgadener-land',
                    'Cham' => 'cham',
                    // 'Coburg' => 'coburg', # no real entries # different layout
                    'Dachau' => 'dachau',
                    'Deggendorf' => 'Deggendorf',
                    'Dillingen' => 'dillingen',
                    'Dingolfing-Landau' => 'dingolfing-landau',
                    'Donau-Ries' => 'donauries',
                    'Ebersberg' => 'ebersberg',
                    'Eichstätt' => 'eichstaett', # single entry since 2020
                    'Erding' => 'erding',
                    'Erlangen' => 'erlangen',
                    'Forchheim' => 'forchheim',
                    'Freising' => 'freising',
                    'Freyung-Grafenau' => 'freyung-grafenau',
                    'Fürstenfeldbruck' => 'fuerstenfeldbruck',
                    'Fürth-Land' => 'fuerth-land',
                    'Fürth-Stadt' => 'fuerth',
                    'Garmisch-Partenkirchen' => 'garmisch-partenkirchen',
                    'Günzburg' => 'guenzburg',
                    'Hassberge' => 'hassberge',
                    'Höchstadt-Herzogenaurach' => 'hoechstadt-herzogenaurach',
                    // 'Hof' => 'kreisgruppehof.bund-naturschutz.com', # non-uniform page
                    'Ingolstadt' => 'ingolstadt',
                    'Kelheim' => 'kelheim',
                    'Kempten' => 'kempten',
                    'Kitzingen' => 'kitzingen',
                    'Kronach' => 'kronach',
                    'Kulmbach' => 'kulmbach',
                    'Landsberg' => 'landsberg',
                    'Landshut' => 'landshut',
                    'Lichtenfeld' => 'lichtenfels',
                    'Lindau' => 'lindau',
                    'Main-Spessart' => 'main-spessart',
                    'Memmingen-Unterallgäu' => 'memmingen-unterallgaeu',
                    'Miesbach' => 'miesbach',
                    'Miltenberg' => 'miltenberg',
                    'Mühldorf am Inn' => 'muehldorf',
                    // 'München' => 'bn-muenchen.de', # non-uniform page
                    'Neu-Ulm' => 'neu-ulm',
                    'Neuburg-Schrobenhausen' => 'neuburg-schrobenhausen',
                    'Neumarkt' => 'neumarkt',
                    'Neustadt/Aisch-Bad Windsheim' => 'neustadt-aisch',
                    'Neustadt/Waldnaab-Weiden' => 'neustadt-weiden',
                    'Nürnberg Stadt' => 'nuernberg-stadt',
                    'Nürnberger Land' => 'nuernberger-land',
                    'Ostallgäu-Kaufbeuren' => 'Ostallgäu-Kaufbeuren',
                    'Passau' => 'passau',
                    'Pfaffenhofen/Ilm' => 'pfaffenhofen',
                    'Regen' => 'regen',
                    'Regensburg' => 'regensburg',
                    'Rhön-Grabfeld' => 'rhoen-grabfeld',
                    'Rosenheim' => 'rosenheim',
                    'Roth' => 'roth',
                    'Rottal-Inn' => 'rottal-inn',
                    'Schwabach' => 'schwabach',
                    'Schwandorf' => 'schwandorf',
                    'Schweinfurt' => 'schweinfurt',
                    'Starnberg' => 'starnberg',
                    'Straubing-Bogen' => 'straubing',
                    'Tirschenreuth' => 'tirschenreuth',
                    'Traunstein' => 'traunstein',
                    'Weilheim-Schongau' => 'weilheim-schongau',
                    'Weißenburg-Gunzenhausen' => 'weissenburg-gunzenhausen',
                    'Wunsiedel' => 'wunsiedel',
                    'Würzburg' => 'wuerzburg',
                ],
            ],
        ],
    ];

    const XPATH_EXPRESSION_ITEM = '//div[@itemtype="http://schema.org/Article"]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/*[@itemprop="headline"]';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/*[@itemprop="description"]/text()';
    const XPATH_EXPRESSION_ITEM_URI = './/a/@href';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/*[@itemprop="datePublished"]/@datetime';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img/@src';

    protected function getSourceUrl()
    {
        return 'https://' . $this->getInput('group') . '.bund-naturschutz.de/aktuelles';
    }
}
