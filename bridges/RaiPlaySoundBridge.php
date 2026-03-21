<?php

declare(strict_types=1);

class RaiPlaySoundBridge extends BridgeAbstract
{
    const NAME = 'RaiPlay Sound';
    const URI = 'https://www.raiplaysound.it';
    const DESCRIPTION = 'Get feeds for shows in the Podcast and Audiolibri sections from RaiPlay Sound.';
    const MAINTAINER = 'giodi';
    const PARAMETERS = [
    [
      'path' => [
        'name' => 'URL',
        'required' => true,
        'title' => 'Insert the URL to the page of a show.',
        'exampleValue' => 'https://www.raiplaysound.it/programmi/ilfalso'
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
        $url = $this->getInput('path');
        $limit = $this->getInput('limit');
        $html = getContents($url);

        $dom = getSimpleHTMLDOM($url);
        $h1 = $dom->find('h1');
        $this->title = $h1[0]->plaintext . self::NAME;
        $episodesJson = [];

        foreach ($dom->find('rps-playlist-action') as $el) {
            $episodesJson[] = json_decode($el->getAttribute('options'), true)['url'];
        }

        if ($limit !== null) {
            $episodesJson = array_slice($episodesJson, 0, $limit);
        }

        foreach ($episodesJson as $ep) {
            $data = json_decode(getContents(self::URI . $ep), true);
            $item = [];
            $item['uri'] = self::URI . $data['weblink'];
            $item['title'] = $data['track_info']['episode_title'];
            $item['timestamp'] = $data['track_info']['date'];
            $item['author'] = $data['podcast_info']['title'];
            $item['content'] = $data['description'];
            $item['enclosures'] = [$data['audio']['url'], self::URI . $data['images']['square']];
            $item['categories'] = array_merge($data['track_info']['genres'], $data['track_info']['sub_genres']);
            $item['uid'] = $data['podcast_info']['uniquename'];
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