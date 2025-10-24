<?php

class AkamaiBridge extends CssSelectorFeedExpanderBridge
{
    const MAINTAINER = 'Mynacol';
    const NAME = 'Akamai Blog';
    const URI = 'https://www.akamai.com/blog';
    const DESCRIPTION = 'Akamai CDN Blog';
    const PARAMETERS = [
        [
            'limit' => self::LIMIT
        ]
    ];

    protected $headers = [
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0',
        'Accept-Language: en',
    ];

    public function collectData()
    {
        $this->collectDataInternal(
            'https://feeds.feedburner.com/akamai/blog',
            'section.main-content',
            '.socialshare, .button, .blogauthor, .taglist, .cmp-prismjs__copy',
            false,
            false,
            true,
            $this->getInput('limit') ?? 5
        );
    }
}
