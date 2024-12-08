<?php

class SubstackBridge extends FeedExpander
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Substack Bridge';
    const URI = 'https://substack.com/';
    const CACHE_TIMEOUT = 3600; //1hour
    const DESCRIPTION = 'Access Substack. Add full content for paywalled posts if you have a session cookie with an active subscription.';

    const CONFIGURATION = [
        'sid' => [
            'required' => false,
        ]
    ];

    const PARAMETERS = [
        '' => [
            'url' => [
                'name' => 'Substack RSS URL',
                'required' => true,
                'type' => 'text',
                'defaultValue' => 'https://newsletter.pragmaticengineer.com/feed',
                'title' => 'Usually https://<blog-url>/feed'
            ]
        ]
    ];

    public function collectData()
    {
        $headers = [];
        if ($this->getOption('sid')) {
            $url_parsed = parse_url($this->getInput('url'));
            $authority = $url_parsed['host'];
            $cookies = [
                'ab_experiment_sampled=%22false%22',
                'substack.sid=' . $this->getOption('sid'),
                'substack.lli=1',
                'intro_popup_last_hidden_at=' . (new DateTime())->format('Y-m-d\TH:i:s.v\Z')
            ];
            $headers = [
                'Authority: ' . $authority,
                'Cache-Control: max-age=0',
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
                'Cookie: ' . implode('; ', $cookies)
            ];
        }
        $this->collectExpandableDatas($this->getInput('url'), -1, $headers);
    }
}
