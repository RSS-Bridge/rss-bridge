<?php

class SubstackBridge extends BridgeAbstract
{
    const NAME = 'Explore Substack';
    const URI = 'https://substack.com/browse/';
    const DESCRIPTION = 'Retrieves articles from Substack based on selected category';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'category' => [
                'name' => 'category',
                'type' => 'list',
                'values' => [
                    'Staff Picks' => 'staff-picks',
                    'Culture' => '96',
                    'Technology' => '4',
                    'Business' => '62',
                    'U.S. Politics' => '76739',
                    'Finance' => '153',
                    'Food & Drink' => '13645',
                    'Sports' => '94',
                    'Art & Illustration' => '15417',
                    'World Politics' => '76740',
                    'Health Politics' => '76741',
                    'News' => '103',
                    'Fashion & Beauty' => '49715',
                    'Music' => '11',
                    'Faith & Spirituality' => '223',
                    'Climate & Environment' => '15414',
                    'Science' => '134',
                    'Literature' => '339',
                    'Fiction' => '284',
                    'Health & Wellness' => '355',
                    'Design' => '61',
                    'Travel' => '109',
                    'Parenting' => '1796',
                    'Philosophy' => '114',
                    'Comics' => '387',
                    'International' => '51282',
                    'Crypto' => '118',
                    'History' => '18',
                    'Humor' => '49692',
                    'Education' => '34',
                ]
            ]
        ]
    ];

    public function collectData()
    {
        $selectedCategory = $this->getInput('category');
        $apiUrl = $this->constructApiUrl($selectedCategory);
        $json = json_decode(getContents($apiUrl));

        foreach ($json->posts as $post) {
            $this->items[] = [
                'uri' => $post->canonical_url,
                'title' => $post->title,
                'timestamp' => $post->post_date,
                'author' => $post->publishedBylines[0]->name,
                'content' => $post->description,
                'enclosures' => [$post->cover_image],
                'uid' => $post->id,
            ];
        }
    }

    private function constructApiUrl($category)
    {
        if ($category === 'staff-picks') {
            return 'https://substack.com/api/v1/reader/posts/staff-picks';
        } else {
            return sprintf(
                'https://substack.com/api/v1/trending?limit=20&category_id=%s',
                $category
            );
        }
    }
}
