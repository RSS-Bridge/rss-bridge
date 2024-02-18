<?php

class ARDAudiothekBridge extends BridgeAbstract
{
    const NAME = 'ARD-Audiothek Bridge';
    const URI = 'https://www.ardaudiothek.de';
    const DESCRIPTION = 'Feed of any show in the ARD-Audiothek, specified by its path';
    const MAINTAINER = 'Mar-Koeh';
    /*
     * The URL Prefix of the API
     * @const APIENDPOINT https-URL of the used endpoint, ending in `/`
     */
    const APIENDPOINT = 'https://api.ardaudiothek.de/';
    /*
     * The requested width of the preview image
     * 448 and 128 have been observed on the wild
     * @const IMAGEWIDTH width in px of the preview image
     */
    const IMAGEWIDTH = 448;
    /*
     * Placeholder that will be replace by IMAGEWIDTH in the preview image URL
     * @const IMAGEWIDTHPLACEHOLDER
     */
    const IMAGEWIDTHPLACEHOLDER = '{width}';
    /*
     * File extension appended to image link in $this->icon
     * @const IMAGEEXTENSION
     */
    const IMAGEEXTENSION = '.jpg';

    const PARAMETERS = [
        [
            'path' => [
                'name' => 'Show Link or ID',
                'required' => true,
                'title' => 'Link to the show page or just its numeric suffix',
                'defaultValue' => 'https://www.ardaudiothek.de/sendung/kalk-welk/10777871/'
            ],
            'limit' => self::LIMIT,
        ]
    ];


    /**
     * Holds the title of the current show
     *
     * @var string
     */
    private $title;

    /**
     * Holds the URI of the show
     *
     * @var string
     */
    private $uri;

    /**
     * Holds the icon of the feed
     *
     */
    private $icon;

    public function collectData()
    {
        $path = $this->getInput('path');
        $limit = $this->getInput('limit');

        $oldTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        $pathComponents = explode('/', $path);
        if (empty($pathComponents)) {
            returnClientError('Path may not be empty');
        }
        if (count($pathComponents) < 2) {
            $showID = $pathComponents[0];
        } else {
            $lastKey = count($pathComponents) - 1;
            $showID = $pathComponents[$lastKey];
            if (strlen($showID) === 0) {
                $showID = $pathComponents[$lastKey - 1];
            }
        }

        $url = self::APIENDPOINT . 'programsets/' . $showID . '/';
        $json1 = getContents($url);
        $data1 = Json::decode($json1, false);
        $processedJSON = $data1->data->programSet;
        if (!$processedJSON) {
            throw new \Exception('Unable to find show id: ' . $showID);
        }

        $answerLength = 1;
        $offset = 0;
        $numberOfElements = 1;

        while ($answerLength != 0 && $offset < $numberOfElements && (is_null($limit) || $offset < $limit)) {
            $json2 = getContents($url . '?offset=' . $offset);
            $data2 = Json::decode($json2, false);
            $processedJSON = $data2->data->programSet;

            $answerLength = count($processedJSON->items->nodes);
            $offset = $offset + $answerLength;
            $numberOfElements = $processedJSON->numberOfElements;

            foreach ($processedJSON->items->nodes as $audio) {
                $item = [];
                $item['uri'] = $audio->sharingUrl;
                $item['title'] = $audio->title;
                $imageSquare = str_replace(self::IMAGEWIDTHPLACEHOLDER, self::IMAGEWIDTH, $audio->image->url1X1);
                $image = str_replace(self::IMAGEWIDTHPLACEHOLDER, self::IMAGEWIDTH, $audio->image->url);
                $item['enclosures'] = [
                    $audio->audios[0]->url,
                    $imageSquare
                ];
                // synopsis in list is shortened, full synopsis is available using one request per item
                $item['content'] = '<img src="' . $image . '" /><p>' . $audio->synopsis . '</p>';
                $item['timestamp'] = $audio->publicationStartDateAndTime;
                $item['uid'] = $audio->id;
                $item['author'] = $audio->programSet->publicationService->title;

                $category = $audio->programSet->editorialCategories->title ?? null;
                if ($category) {
                    $item['categories'] = [$category];
                }

                $item['itunes'] = [
                    'duration' => $audio->duration,
                ];

                $this->items[] = $item;
            }
        }
        $this->title = $processedJSON->title;
        $this->uri = $processedJSON->sharingUrl;
        $this->icon = str_replace(self::IMAGEWIDTHPLACEHOLDER, self::IMAGEWIDTH, $processedJSON->image->url1X1);
        // add image file extension to URL so icon is shown in generated RSS feeds, see
        // https://github.com/RSS-Bridge/rss-bridge/blob/4aed05c7b678b5673386d61374bba13637d15487/formats/MrssFormat.php#L76
        $this->icon = $this->icon . self::IMAGEEXTENSION;

        $this->items = array_slice($this->items, 0, $limit);

        date_default_timezone_set($oldTz);
    }

    /** {@inheritdoc} */
    public function getURI()
    {
        if (!empty($this->uri)) {
            return $this->uri;
        }
        return parent::getURI();
    }

    /** {@inheritdoc} */
    public function getName()
    {
        if (!empty($this->title)) {
            return $this->title;
        }
        return parent::getName();
    }

    /** {@inheritdoc} */
    public function getIcon()
    {
        if (!empty($this->icon)) {
            return $this->icon;
        }
        return parent::getIcon();
    }
}
