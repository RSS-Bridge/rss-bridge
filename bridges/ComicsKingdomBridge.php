<?php

class ComicsKingdomBridge extends BridgeAbstract
{
    const MAINTAINER = 'TReKiE';
    // const MAINTAINER = 'stjohnjohnson';
    const NAME = 'Comics Kingdom Unofficial RSS';
    const URI = 'https://wp.comicskingdom.com/wp-json/wp/v2/ck_comic';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'Comics Kingdom Unofficial RSS';
    const PARAMETERS = [ [
        'comicname' => [
            'name' => 'Name of comic',
            'type' => 'text',
            'exampleValue' => 'mutts',
            'title' => 'The name of the comic in the URL after https://comicskingdom.com/',
            'required' => true
        ],
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'title' => 'The number of recent comics to get',
            'defaultValue' => 10
        ]
    ]];

    protected $comicName;

    public function collectData()
    {
        $json = getContents($this->getURI());
        $data = json_decode($json, false);

        if (isset($data[0]->_embedded->{'wp:term'}[0][0])) {
            $this->comicName = $data[0]->_embedded->{'wp:term'}[0][0]->name;
        }

        foreach ($data as $comicitem) {
            $item = [];

            $item['id'] = $comicitem->id;
            $item['uri'] = $comicitem->yoast_head_json->og_url;
            $item['author'] = str_ireplace('By ', '', $comicitem->ck_comic_byline);
            $item['title'] = $comicitem->yoast_head_json->title;
            $item['timestamp'] = $comicitem->date;
            $item['content'] = '<img src="' . $comicitem->yoast_head_json->og_image[0]->url . '" />';
            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('comicname'))) {
            $params = [
                'ck_feature'        => $this->getInput('comicname'),
                'per_page'          => $this->getInput('limit'),
                'date_inclusive'    => 'true',
                'order'             => 'desc',
                'page'              => '1',
                '_embed'            => 'true'
            ];

            return self::URI . '?' . http_build_query($params);
        }

        return parent::getURI();
    }

    public function getName()
    {
        if ($this->comicName) {
            return $this->comicName . ' - Comics Kingdom';
        }

        return parent::getName();
    }
}
