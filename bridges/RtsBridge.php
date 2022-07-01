<?php

class RtsBridge extends BridgeAbstract
{
    const NAME = 'Radio Télévision Suisse';
    const URI = 'https://www.rts.ch/';
    const MAINTAINER = 'imagoiq';
    const DESCRIPTION = 'Returns newest videos from RTS';

    const PARAMETERS = [
        'ID de l\'émission' => [
            'idShow' => [
                'name' => 'Show id',
                'required' => true,
                'exampleValue' => 385418,
                'title' => 'ex. 385418 pour
				https://www.rts.ch/play/tv/emission/a-bon-entendeur?id=385418'
            ]
        ],
        'ID de la section' => [
            'idSection' => [
                'name' => 'Section id',
                'required' => true,
                'exampleValue' => 'ce802a54-8877-49cc-acd6-8d244762829b',
                'title' => 'ex. ce802a54-8877-49cc-acd6-8d244762829b pour
				https://www.rts.ch/play/tv/detail/humour?id=ce802a54-8877-49cc-acd6-8d244762829b'
            ]
        ]
    ];

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'ID de l\'émission':
                $showId = $this->getInput('idShow');

                $url = 'https://www.rts.ch/play/v3/api/rts/production/videos-by-show-id?showId='
                . $showId;
                break;
            case 'ID de la section':
                $sectionId = $this->getInput('idSection');

                $url = 'https://www.rts.ch/play/v3/api/rts/production/media-section?sectionId='
                . $sectionId;
                break;
        }

        $header = [];
        $input = getContents($url, $header);
        $input_json = json_decode($input, true);

        foreach ($input_json['data']['data'] as $element) {
            $item = [];
            $item['uri'] = 'https://www.rts.ch/play/tv/-/video/-?urn=' . $element['urn'];
            $item['uid'] = $element['id'];

            $item['timestamp'] = strtotime($element['date']);
            $item['title'] = $element['show']['title'] . ' - ' . $element['title'];

            $item['duration'] = round((int)$element['duration'] / 60000);
            $durationInHour = date('g\hi', mktime(0, $item['duration']));
            $durationInMin = date('i\m\i\n', mktime(0, $item['duration']));
            $durationText = $item['duration'] > 60 ? $durationInHour : $durationInMin;

            $item['content'] = $element['description']
            . '<br/><br/>'
            . $durationText
            . '<br><a href="'
            . $item['uri']
            . '"><img src="'
            . $element['imageUrl']
            . '/scale/width/700" alt=""/></a>';

            $this->items[] = $item;
        }
    }
}
