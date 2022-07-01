<?php

class LegoIdeasBridge extends BridgeAbstract
{
    const NAME = 'Lego Ideas';
    const URI = 'https://ideas.lego.com/';
    const DESCRIPTION = 'Community Supported Lego Builds';
    const MAINTAINER = 'sal0max';
    const CACHE_TIMEOUT = 60 * 60 * 2; // 2h
    const PARAMETERS = [ [
            'support_value_min' => [
                'name' => 'Minimum Supporters',
                'title' => 'The number of people that need to have supported a project at minimum.
Once a project reaches 10,000 supporters, it gets reviewed by the lego experts.',
                'type' => 'number',
                'defaultValue' => 1000
            ],
            'idea_phase' => [
                'name' => 'Idea Phase',
                'type' => 'list',
                'values' => [
                    'Gathering Support' => 'idea_gathering_support',
                    'Achieved Support' => 'idea_achieved_support',
                    'In Review' => 'idea_in_review',
                    'Approved Ideas' => 'idea_idea_approved',
                    'Not Approved Ideas' => 'idea_idea_not_approved',
                    'On Shelves' => 'idea_on_shelves',
                    'Expired Ideas' => 'idea_expired_ideas',
                ],
                'defaultValue' => 'idea_gathering_support'
            ]
        ]
    ];

    public function getURI()
    {
        // link to the corresponding page on the website, not the api endpoint
        return self::URI . 'search/global_search/ideas'
            . "?support_value={$this->getInput('support_value_min')}"
            . '&support_value=10000'
            . "&idea_phase={$this->getInput('idea_phase')}"
            . '&sort=most_recent';
    }

    public function collectData()
    {
        $header = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        $opts = [
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $this->getHttpPostData()
        ];
        $responseData = getContents($this->getHttpPostURI(), $header, $opts) or
                returnServerError('Unable to query Lego Ideas API.');

        foreach (json_decode($responseData)->results as $project) {
            preg_match('/datetime=\"(\S+)\"/', $project->entity->published_at, $date_matches);
            $datetime = $date_matches[1];
            $link     = self::URI . $project->entity->view_url;
            $title    = $project->entity->title;
            $desc     = $project->entity->content;
            $imageUrl = $project->entity->image_url;
            $creator  = $project->entity->creator->alias;
            $uuid     = $project->entity->uuid;

            $item = [
                'uri'       => $link,
                'title'     => $title,
                'timestamp' => strtotime($datetime),
                'author'    => $creator,
                'content'   => <<<EOD
<p><img src="{$imageUrl}" alt="{$title}"/></p>
<p>{$desc}</p>
EOD
            ];
            $this->items[] = $item;
        }
    }

    /**
     * Returns the API endpoint
     */
    private function getHttpPostURI()
    {
        return self::URI . '/search/global_search/ideas';
    }

    /**
     * Returns the API query
     */
    private function getHttpPostData()
    {
        $phase = $this->getInput('idea_phase');
        $minSupporters = $this->getInput('support_value_min');

        return <<<EOD
{ "filters": {
	 "idea_phase": [ "$phase" ],
	 "support_value": [ $minSupporters, 10000 ]
},
"sort": [ "most_recent:desc" ]
}
EOD;
    }
}
