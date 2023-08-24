<?php

class IndiegogoBridge extends BridgeAbstract
{
    const NAME = 'Indiegogo';
    const URI = 'https://www.indiegogo.com';
    const DESCRIPTION = 'Fetch projects by category';
    const MAINTAINER = 'bockiii';
    const PARAMETERS = [
        'global' => [
            'timing' => [
                'name' => 'Project Timing',
                'type' => 'list',
                'values' => [
                    'All' => 'all',
                    'Launching Soon' => 'launching_soon',
                    'Just Launched' => 'just_launched',
                    'Ending Soon' => 'ending_soon',
                ],
                'defaultValue' => 'Just Launched'
            ],
        ],
        'All Categories' => [],
        'Tech & Innovation' => [
            'tech' => [
                'name' => 'Tech & Innovation',
                'type' => 'list',
                'values' => [
                    'All' => 'all',
                    'Audio' => 'Audio',
                    'Camera Gear' => 'Camera Gear',
                    'Education' => 'Education',
                    'Energy & Green Tech' => 'Energy & Green Tech',
                    'Fashion & Wearables' => 'Fashion & Wearables',
                    'Food & Beverages' => 'Food & Beverages',
                    'Health & Fitness' => 'Health & Fitness',
                    'Home' => 'Home',
                    'Phones & Accessories' => 'Phones & Accessories',
                    'Productivity' => 'Productivity',
                    'Transportation' => 'Transportation',
                    'Travel & Outdoors' => 'Travel & Outdoors',
                ],
            ],
        ],
        'Creative Works' => [
            'creative' => [
                'name' => 'Creative Works',
                'type' => 'list',
                'values' => [
                    'All' => 'all',
                    'Comics' => 'Comics',
                    'Dance & Theater' => 'Dance & Theater',
                    'Film' => 'Film',
                    'Music' => 'Music',
                    'Photography' => 'Photography',
                    'Podcasts, Blogs & Vlogs' => 'Podcasts, Blogs & Vlogs',
                    'Tabletop Games' => 'Tabletop Games',
                    'Video Games' => 'Video Games',
                    'Web Series & TV Shows' => 'Web Series & TV Shows',
                    'Writing & Publishing' => 'Writing & Publishing',
                ],
            ],
        ],
        'Community Projects' => [
            'community' => [
                'name' => 'Community Projects',
                'type' => 'list',
                'values' => [
                    'All' => 'all',
                    'Culture' => 'Culture',
                    'Environment' => 'Environment',
                    'Human Rights' => 'Human Rights',
                    'Local Businesses' => 'Local Businesses',
                    'Wellness' => 'Wellness',
                ],
            ],
        ],
    ];

    const CACHE_TIMEOUT = 21600; // 6 hours

    public function collectData()
    {
        $url = 'https://www.indiegogo.com/private_api/discover';
        $data_array = $this->getCategories();

        $header = ['Content-type: application/json'];
        $opts = [CURLOPT_POSTFIELDS => json_encode($data_array)];
        $html = getContents($url, $header, $opts);
        $html_response = json_decode($html, true);

        foreach ($html_response['response']['discoverables'] as $obj) {
            $this->items[] = [
                'title' => $obj['title'],
                'uri' => $this->getURI() . $obj['clickthrough_url'],
                'timestamp' => $obj['open_date'],
                'enclosures' => $obj['image_url'],
                'content' => '<a href=' . $this->getURI() . $obj['clickthrough_url']
                . '><img src="' . $obj['image_url'] . '" /></a><br><br><b>'
                . $obj['title'] . '</b><br><br><small>'
                . $obj['tagline'] . '</small><br>',
            ];
        }
    }

    protected function getCategories()
    {
        $selection = [
            'sort'  => 'trending',
            'project_type'  => 'campaign',
            'project_timing' => $this->getInput('timing'),
            'category_main' => null,
            'category_top_level' => null,
            'page_num'  => 1,
            'per_page'  => 12,
            'q' => '',
            'tags'  => []
        ];

        switch ($this->queriedContext) {
            case 'Tech & Innovation':
                $selection['category_top_level'] = $this->queriedContext;
                if ($this->getInput('tech') != 'all') {
                    $selection['category_main'] = $this->getInput('tech');
                }
                break;
            case 'Creative Works':
                $selection['category_top_level'] = $this->queriedContext;
                if ($this->getInput('creative') != 'all') {
                    $selection['category_main'] = $this->getInput('creative');
                }
                break;
            case 'Community Projects':
                $selection['category_top_level'] = $this->queriedContext;
                if ($this->getInput('community') != 'all') {
                    $selection['category_main'] = $this->getInput('community');
                }
                break;
        }
        return $selection;
    }
}
