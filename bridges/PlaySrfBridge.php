<?php

declare(strict_types=1);

class PlaySrfBridge extends BridgeAbstract
{
    const NAME = 'Play SRF';
    const URI = 'https://www.srf.ch/play/tv';
    const DESCRIPTION = 'Feed of any show in the Play SRF portal, specified by its ID';
    const MAINTAINER = 'giodi';
    const PARAMETERS = [
    [
      'showId' => [
        'name' => 'Show Link or ID',
        'required' => true,
        'title' => 'Insert the URL to the page of a show.',
        'exampleValue' => 'https://www.srf.ch/play/tv/sendung/arena?id=09784065-687b-4b60-bd23-9ed0d2d43cdc'
      ],
      'embed' => [
        'type' => 'checkbox',
        'name' => 'Embed',
        'required' => false,
        'title' => 'Check if you want to include an embed of the episode in the feed items.',
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

    public function collectData(): void
    {
        preg_match('/[a-z0-9]{8}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{12}$/s', $this->getInput('showId'), $matches);
        $url = $matches[0];
        $embed = $this->getInput('embed');
        $limit = $this->getInput('limit');

        $raw = getContents('https://www.srf.ch/play/v3/api/srf/production/videos-by-show-id?showId=' . $url);
        $jsonShowVideos = json_decode($raw, true);

        $this->title = $jsonShowVideos['data']['data'][0]['show']['title'] ?? 'Play SRF';
        $episodes = $jsonShowVideos['data']['data'];

        if ($limit !== null) {
            $episodes = array_slice($episodes, 0, $limit);
        }

        foreach ($episodes as $ep) {
            $content = '';

            if ($embed === true) {
                $content .= '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;">';
                $content .= '<iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" width="560" height="315" ';
                $content .= 'src="https://www.srf.ch/play/embed?urn=' . $ep['urn'] . '&subdivisions=false" allowfullscreen ';
                $content .= 'allow="geolocation *; autoplay; encrypted-media"></iframe></div>';
            }

            $content .= $ep['description'] === '' ? '<p>' . nl2br($ep['lead'], false) . '</p>' : '<p>' . nl2br($ep['description'], false) . '</p>';

            $item = [];
            $item['uri'] = 'https://www.srf.ch/play/tv/-/video/-?urn=' . $ep['urn'];
            $item['title'] = $ep['title'];
            $item['timestamp'] = $ep['date'];
            $item['author'] = $ep['show']['title'];
            $item['content'] = $content;
            $item['uid'] = $ep['urn'];
            $item['duration'] = $ep['duration'];
            $this->items[] = $item;
        }
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
