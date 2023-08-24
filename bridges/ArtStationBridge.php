<?php

class ArtStationBridge extends BridgeAbstract
{
    const NAME = 'ArtStation';
    const URI = 'https://www.artstation.com';
    const DESCRIPTION = 'Fetches the latest ten artworks from a search query on ArtStation.';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 3600; // 1h

    const PARAMETERS = [
        'Search Query' => [
            'q' => [
                'name' => 'Search term',
                'required' => true,
                'exampleValue'  => 'bird'
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://www.artstation.com/assets/favicon-58653022bc38c1905ac7aa1b10bffa6b.ico';
    }

    public function getName()
    {
        return self::NAME . ': ' . $this->getInput('q');
    }

    private function fetchSearch($searchQuery)
    {
        $data = '{"query":"' . $searchQuery . '","page":1,"per_page":50,"sorting":"date",';
        $data .= '"pro_first":"1","filters":[],"additional_fields":[]}';

        $header = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true
        ];

        $jsonSearchURL = self::URI . '/api/v2/search/projects.json';
        $jsonSearchStr = getContents($jsonSearchURL, $header, $opts);
        return json_decode($jsonSearchStr);
    }

    private function fetchProject($hashID)
    {
        $jsonProjectURL = self::URI . '/projects/' . $hashID . '.json';
        $jsonProjectStr = getContents($jsonProjectURL);
        return json_decode($jsonProjectStr);
    }

    public function collectData()
    {
        $searchTerm = $this->getInput('q');
        $jsonQuery = $this->fetchSearch($searchTerm);

        foreach ($jsonQuery->data as $media) {
            // get detailed info about media item
            $jsonProject = $this->fetchProject($media->hash_id);

            // create item
            $item = [];
            $item['title'] = $media->title;
            $item['uri'] = $media->url;
            $item['timestamp'] = strtotime($jsonProject->published_at);
            $item['author'] = $media->user->full_name;
            $item['categories'] = implode(',', $jsonProject->tags);

            $item['content'] = '<a href="'
                . $media->url
                . '"><img style="max-width: 100%" src="'
                . $jsonProject->cover_url
                . '"></a><p>'
                . $jsonProject->description
                . '</p>';

            $numAssets = count($jsonProject->assets);

            if ($numAssets > 1) {
                $item['content'] .= '<p><a href="'
                    . $media->url
                    . '">Project contains '
                    . ($numAssets - 1)
                    . ' more item(s).</a></p>';
            }

            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }
}
