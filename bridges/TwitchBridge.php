<?php

class TwitchBridge extends BridgeAbstract
{
    const MAINTAINER = 'Roliga';
    const NAME = 'Twitch Bridge';
    const URI = 'https://twitch.tv/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION = 'Twitch channel videos';
    const PARAMETERS = [ [
        'channel' => [
            'name' => 'Channel',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'criticalrole',
            'title' => 'Lowercase channel name as seen in channel URL'
        ],
        'type' => [
            'name' => 'Type',
            'type' => 'list',
            'values' => [
                'All' => 'all',
                'Archive' => 'archive',
                'Highlights' => 'highlight',
                'Uploads' => 'upload',
                'Past Premieres' => 'past_premiere',
                'Premiere Uploads' => 'premiere_upload'
            ],
            'defaultValue' => 'archive'
        ]
    ]];

    const BROADCAST_TYPES = [
        'all' => [
            'ARCHIVE',
            'HIGHLIGHT',
            'UPLOAD',
            'PAST_PREMIERE',
            'PREMIERE_UPLOAD'
        ],
        'archive' => 'ARCHIVE',
        'highlight' => 'HIGHLIGHT',
        'upload' => 'UPLOAD',
        'past_premiere' => 'PAST_PREMIERE',
        'premiere_upload' => 'PREMIERE_UPLOAD'
    ];

    public function collectData()
    {
        $query = <<<'EOD'
query VODList($channel: String!, $types: [BroadcastType!]) {
  user(login: $channel) {
    displayName
    videos(types: $types, sort: TIME) {
      edges {
        node {
          id
          title
          publishedAt
          lengthSeconds
          viewCount
          thumbnailURLs(width: 640, height: 360)
          previewThumbnailURL(width: 640, height: 360)
          description
          tags
          contentTags {
            isLanguageTag
            localizedName
          }
          game {
            displayName
          }
          moments(momentRequestType: VIDEO_CHAPTER_MARKERS) {
            edges {
              node {
                description
                positionMilliseconds
              }
            }
          }
        }
      }
    }
  }
}
EOD;
        $channel = $this->getInput('channel');
        $type = $this->getInput('type');
        $variables = [
            'channel' => $channel,
            'types' => self::BROADCAST_TYPES[$type]
        ];
        $response = $this->apiRequest($query, $variables);
        $data = $response->data;
        if ($data->user === null) {
            throw new \Exception(sprintf('Unable to find channel `%s`', $channel));
        }

        $user = $data->user;
        if ($user->videos === null) {
            // twitch regularly does this for unknown reasons
            $this->debug->info('Twitch returned empty set of videos', ['data' => $data]);
            return;
        }

        foreach ($user->videos->edges as $edge) {
            $video = $edge->node;

            $url = 'https://www.twitch.tv/videos/' . $video->id;

            $item = [
                'uri' => $url,
                'title' => $video->title,
                'timestamp' => $video->publishedAt,
                'author' => $user->displayName,
            ];

            // Add categories for tags and played game
            $item['categories'] = $video->tags;
            if (!is_null($video->game)) {
                $item['categories'][] = $video->game->displayName;
            }

            $contentTags = $video->contentTags ?? [];
            foreach ($contentTags as $tag) {
                if (!$tag->isLanguageTag) {
                    $item['categories'][] = $tag->localizedName;
                }
            }

            // Add enclosures for thumbnails from a few points in the video
            // Thumbnail list has duplicate entries sometimes so remove those
            $item['enclosures'] = array_unique($video->thumbnailURLs);

            /*
             * Content format example:
             *
             * [Preview Image]
             *
             * Some optional video description.
             *
             * Duration: 1:23:45
             * Views: 123
             *
             * Played games:
             * * 00:00:00 Game 1
             * * 00:12:34 Game 2
             *
             */
            $item['content'] = '<p><a href="'
                . $url
                . '"><img src="'
                . $video->previewThumbnailURL
                . '" /></a></p><p>'
                . $video->description // in markdown format
                . '</p><p><b>Duration:</b> '
                . $this->formatTimestampTime($video->lengthSeconds)
                . '<br/><b>Views:</b> '
                . $video->viewCount
                . '</p>';

            // Add played games list to content
            $item['content'] .= '<p><b>Played games:</b><ul>';

            $momentEdges = $video->moments->edges ?? [];
            if (count($momentEdges) > 0) {
                foreach ($momentEdges as $momentEdge) {
                    $moment = $momentEdge->node;

                    $item['categories'][] = $moment->description;
                    $item['content'] .= '<li><a href="'
                        . $url
                        . '?t='
                        . $this->formatQueryTime($moment->positionMilliseconds / 1000)
                        . '">'
                        . $this->formatTimestampTime($moment->positionMilliseconds / 1000)
                        . '</a> - '
                        . $moment->description
                        . '</li>';
                }
            } else {
                $item['content'] .= '<li><a href="'
                    . $url
                    . '">00:00:00</a> - '
                    . ($video->game ? $video->game->displayName : 'No Game')
                    . '</li>';
            }
            $item['content'] .= '</ul></p>';

            $item['categories'] = array_unique($item['categories']);

            $this->items[] = $item;
        }
    }

    // e.g. 01:53:27
    private function formatTimestampTime($seconds)
    {
        $floor = floor($seconds / 3600);
        $i = intval($seconds / 60) % 60;
        $i1 = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $floor, $i, $i1);
    }

    // e.g. 01h53m27s
    private function formatQueryTime($seconds)
    {
        $floor = floor($seconds / 3600);
        $i = intval($seconds / 60) % 60;
        $i1 = $seconds % 60;

        return sprintf('%02dh%02dm%02ds', $floor, $i, $i1);
    }

    /**
     * GraphQL: https://graphql.org/
     * Tool for developing/testing queries: https://github.com/skevy/graphiql-app
     *
     * Official instructions for obtaining your own client ID can be found here:
     * https://dev.twitch.tv/docs/v5/#getting-a-client-id
     */
    private function apiRequest($query, $variables)
    {
        $request = [
            'query'     => $query,
            'variables' => $variables,
        ];
        $headers = [
            'Client-ID: kimne78kx3ncx6brgo4mv6wki5h1ko',
        ];
        $opts = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request),
        ];
        $json = getContents('https://gql.twitch.tv/gql', $headers, $opts);
        $result = Json::decode($json, false);
        return $result;
    }

    public function getName()
    {
        if (!is_null($this->getInput('channel'))) {
            return $this->getInput('channel') . ' twitch videos';
        }

        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('channel'))) {
            return self::URI . $this->getInput('channel');
        }

        return parent::getURI();
    }

    public function detectParameters($url)
    {
        $params = [];

        // Matches e.g. https://www.twitch.tv/someuser/videos?filter=archives
        $regex = '/^(https?:\/\/)?
			(www\.)?
			twitch\.tv\/
			([^\/&?\n]+)
			\/videos\?.*filter=
			(all|archive|highlight|upload)/x';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['channel'] = urldecode($matches[3]);
            $params['type'] = $matches[4];
            return $params;
        }

        return null;
    }
}
