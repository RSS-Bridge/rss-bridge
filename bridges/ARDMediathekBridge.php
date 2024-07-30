<?php

class ARDMediathekBridge extends BridgeAbstract
{
    const NAME = 'ARD-Mediathek Bridge';
    const URI = 'https://www.ardmediathek.de';
    const DESCRIPTION = 'Feed of any series in the ARD-Mediathek, specified by its path';
    const MAINTAINER = 'yue-dongchen';
    /*
     * Number of Items to be requested from ARDmediathek API
     * 12 has been observed on the wild
     * 29 is the highest successfully tested value
     * More Items could be fetched via pagination
     * The JSON-field pagination holds more information on that
     * @const PAGESIZE number of requested items
     */
    const PAGESIZE = 29;
    /*
     * The URL Prefix of the (Webapp-)API
     * @const APIENDPOINT https-URL of the used endpoint
     */
    const APIENDPOINT = 'https://api.ardmediathek.de/page-gateway/widgets/ard/asset/';
    /*
     * The URL prefix of the video link
     * URLs from the webapp include a slug containing titles of show, episode, and tv station.
     * It seems to work without that.
     * @const VIDEOLINKPREFIX https-URL prefix of video links
     */
    const VIDEOLINKPREFIX = 'https://www.ardmediathek.de/video/';
    /*
     * The requested width of the preview image
     * 432 has been observed on the wild
     * The webapp seems to also compute and add the height value
     * It seems to works without that.
     * @const IMAGEWIDTH width in px of the preview image
     */
    const IMAGEWIDTH = 432;
    /*
     * Placeholder that will be replace by IMAGEWIDTH in the preview image URL
     * @const IMAGEWIDTHPLACEHOLDER
     */
    const IMAGEWIDTHPLACEHOLDER = '{width}';
    /**
     * Title of the current show
     * @var string
     */
    private $title;

    const PARAMETERS = [
        [
            'path' => [
                'name' => 'Show Link or ID',
                'required' => true,
                'title' => 'Link to the show page or just its alphanumeric suffix',
                'defaultValue' => 'https://www.ardmediathek.de/sendung/45-min/Y3JpZDovL25kci5kZS8xMzkx/'
            ]
        ]
    ];

    public function collectData()
    {
        $oldTz = date_default_timezone_get();

        date_default_timezone_set('Europe/Berlin');

        $pathComponents = explode('/', $this->getInput('path'));
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

        $url = self::APIENDPOINT . $showID . '?pageSize=' . self::PAGESIZE;
        $rawJSON = getContents($url);
        $processedJSON = json_decode($rawJSON);

        foreach ($processedJSON->teasers as $video) {
            $item = [];
            // there is also ->links->self->id, ->links->self->urlId, ->links->target->id, ->links->target->urlId
            $item['uri'] = self::VIDEOLINKPREFIX . $video->id . '/';
            // there is also ->mediumTitle and ->shortTitle
            $item['title'] = $video->longTitle;
            // in the test, aspect16x9 was the only child of images, not sure whether that is always true
            $item['enclosures'] = [
                str_replace(self::IMAGEWIDTHPLACEHOLDER, self::IMAGEWIDTH, $video->images->aspect16x9->src)
            ];
            $item['content'] = '<img src="' . $item['enclosures'][0] . '" /><p>';
            $item['timestamp'] = $video->broadcastedOn;
            $item['uid'] = $video->id;
            $item['author'] = $video->publicationService->name;
            $this->items[] = $item;
        }

        $this->title = $processedJSON->title;

        date_default_timezone_set($oldTz);
    }

    /** {@inheritdoc} */
    public function getName()
    {
        if (!empty($this->title)) {
            return $this->title;
        }
        return parent::getName();
    }
}
