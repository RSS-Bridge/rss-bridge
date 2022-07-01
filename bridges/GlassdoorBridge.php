<?php

class GlassdoorBridge extends BridgeAbstract
{
    // Contexts
    const CONTEXT_BLOG   = 'Blogs';
    const CONTEXT_REVIEW = 'Company Reviews';
    const CONTEXT_GLOBAL = 'global';

    // Global context parameters
    const PARAM_LIMIT = 'limit';

    // Blog context parameters
    const PARAM_BLOG_TYPE = 'blog_type';
    const PARAM_BLOG_FULL = 'full_article';

    const BLOG_TYPE_HOME             = 'Home';
    const BLOG_TYPE_COMPANIES_HIRING = 'Companies Hiring';
    const BLOG_TYPE_CAREER_ADVICE    = 'Career Advice';
    const BLOG_TYPE_INTERVIEWS       = 'Interviews';

    // Review context parameters
    const PARAM_REVIEW_COMPANY = 'company';

    const MAINTAINER = 'logmanoriginal';
    const NAME = 'Glassdoor Bridge';
    const URI = 'https://www.glassdoor.com/';
    const DESCRIPTION = 'Returns feeds for blog posts and company reviews';
    const CACHE_TIMEOUT = 86400; // 24 hours

    const PARAMETERS = [
        self::CONTEXT_BLOG => [
            self::PARAM_BLOG_TYPE => [
                'name' => 'Blog type',
                'type' => 'list',
                'title' => 'Select the blog you want to follow',
                'values' => [
                    self::BLOG_TYPE_HOME                => 'blog/',
                    self::BLOG_TYPE_COMPANIES_HIRING    => 'blog/companies-hiring/',
                    self::BLOG_TYPE_CAREER_ADVICE       => 'blog/career-advice/',
                    self::BLOG_TYPE_INTERVIEWS          => 'blog/interviews/',
                ]
            ],
            self::PARAM_BLOG_FULL => [
                'name' => 'Full article',
                'type' => 'checkbox',
                'title' => 'Enable to return the full article for each post'
            ],
        ],
        self::CONTEXT_REVIEW => [
            self::PARAM_REVIEW_COMPANY => [
                'name' => 'Company URL',
                'type' => 'text',
                'required' => true,
                'title' => 'Paste the company review page URL here!',
                'exampleValue' => 'https://www.glassdoor.com/Reviews/GitHub-Reviews-E671945.htm'
            ]
        ],
        self::CONTEXT_GLOBAL => [
            self::PARAM_LIMIT => [
                'name' => 'Limit',
                'type' => 'number',
                'defaultValue' => -1,
                'title' => 'Specifies the maximum number of items to return (default: All)'
            ]
        ]
    ];

    public function getURI()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_BLOG:
                return self::URI . $this->getInput(self::PARAM_BLOG_TYPE);
            case self::CONTEXT_REVIEW:
                return $this->filterCompanyURI($this->getInput(self::PARAM_REVIEW_COMPANY));
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $url = $this->getURI();
        $html = getSimpleHTMLDOM($url);
        $html = defaultLinkTo($html, $url);
        $limit = $this->getInput(self::PARAM_LIMIT);

        switch ($this->queriedContext) {
            case self::CONTEXT_BLOG:
                $this->collectBlogData($html, $limit);
                break;
            case self::CONTEXT_REVIEW:
                $this->collectReviewData($html, $limit);
                break;
        }
    }

    private function collectBlogData($html, $limit)
    {
        $posts = $html->find('div.post')
            or returnServerError('Unable to find blog posts!');

        foreach ($posts as $post) {
            $item = [];

            $item['uri'] = $post->find('a', 0)->href;
            $item['title'] = $post->find('h3', 0)->plaintext;
            $item['content'] = $post->find('p', 0)->plaintext;
            $item['author'] = $post->find('p', -2)->plaintext;
            $item['timestamp'] = strtotime($post->find('p', -1)->plaintext);

            // TODO: fetch entire blog post content
            $this->items[] = $item;

            if ($limit > 0 && count($this->items) >= $limit) {
                return;
            }
        }
    }

    private function collectReviewData($html, $limit)
    {
        $reviews = $html->find('#ReviewsFeed li[id^="empReview]')
            or returnServerError('Unable to find reviews!');

        foreach ($reviews as $review) {
            $item = [];

            $item['uri'] = $review->find('a.reviewLink', 0)->href;

            // Not all reviews have a title
            $item['title'] = $review->find('h2', 0)->plaintext ?? 'Glassdoor review';

            [$date, $author] = explode('-', $review->find('span.authorInfo', 0)->plaintext);

            $item['author'] = trim($author);

            $createdAt = DateTimeImmutable::createFromFormat('F m, Y', trim($date));
            if ($createdAt) {
                $item['timestamp'] = $createdAt->getTimestamp();
            }

            $item['content'] = $review->find('.px-std', 2)->text();

            $this->items[] = $item;

            if ($limit > 0 && count($this->items) >= $limit) {
                return;
            }
        }
    }

    private function filterCompanyURI($uri)
    {
        /* Make sure the URI is a valid review page. Unfortunately there is no
         * simple way to determine if the URI is valid, because of automagic
         * redirection and strange naming conventions.
         */
        if (
            !filter_var(
                $uri,
                FILTER_VALIDATE_URL,
                FILTER_FLAG_PATH_REQUIRED
            )
        ) {
            returnClientError('The specified URL is invalid!');
        }

        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        $path = parse_url($uri, PHP_URL_PATH);
        $parts = explode('/', $path);

        $allowed_strings = [
            'de-DE' => 'Bewertungen',
            'en-AU' => 'Reviews',
            'nl-BE' => 'Reviews',
            'fr-BE' => 'Avis',
            'en-CA' => 'Reviews',
            'fr-CA' => 'Avis',
            'fr-FR' => 'Avis',
            'en-IN' => 'Reviews',
            'en-IE' => 'Reviews',
            'nl-NL' => 'Reviews',
            'de-AT' => 'Bewertungen',
            'de-CH' => 'Bewertungen',
            'fr-CH' => 'Avis',
            'en-GB' => 'Reviews',
            'en'    => 'Reviews'
        ];

        if (!in_array($parts[1], $allowed_strings)) {
            returnClientError('Please specify a URL pointing to the companies review page!');
        }

        return $uri;
    }
}
