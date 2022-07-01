<?php

class GoogleGroupsBridge extends XPathAbstract
{
    const NAME = 'Google Groups Bridge';
    const DESCRIPTION = 'Returns the latest posts on a Google Group';
    const URI = 'https://groups.google.com';
    const PARAMETERS = [ [
        'group' => [
            'name' => 'Group id',
            'title' => 'The string that follows /g/ in the URL',
            'exampleValue' => 'governance',
            'required' => true
        ],
        'account' => [
            'name' => 'Account id',
            'title' => 'Some Google groups have an additional id following /a/ in the URL',
            'exampleValue' => 'mozilla.org',
            'required' => false
        ]
    ]];
    const CACHE_TIMEOUT = 3600;

    const TEST_DETECT_PARAMETERS = [
        'https://groups.google.com/a/mozilla.org/g/announce' => [
            'account' => 'mozilla.org', 'group' => 'announce'
        ],
        'https://groups.google.com/g/ansible-project' => [
            'account' => null, 'group' => 'ansible-project'
        ],
    ];

    const XPATH_EXPRESSION_ITEM = '//div[@class="yhgbKd"]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/span[@class="o1DPKc"]';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/span[@class="WzoK"]';
    const XPATH_EXPRESSION_ITEM_URI = './/a[@class="ZLl54"]/@href';
    const XPATH_EXPRESSION_ITEM_AUTHOR = './/span[@class="z0zUgf"][last()]';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/div[@class="tRlaM"]';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = '';
    const XPATH_EXPRESSION_ITEM_CATEGORIES = '';
    const SETTING_FIX_ENCODING = true;

    protected function getSourceUrl()
    {
        $source = self::URI;

        $account = $this->getInput('account');
        if ($account) {
            $source = $source . '/a/' . $account;
        }
        return $source . '/g/' . $this->getInput('group');
    }

    protected function provideWebsiteContent()
    {
        return defaultLinkTo(getContents($this->getSourceUrl()), self::URI);
    }

    const URL_REGEX = '#^https://groups.google.com(?:/a/(?<account>\S+))?(?:/g/(?<group>\S+))#';

    public function detectParameters($url)
    {
        $params = [];
        if (preg_match(self::URL_REGEX, $url, $matches)) {
            $params['group'] = $matches['group'];
            $params['account'] = $matches['account'];
            return $params;
        }
        return null;
    }
}
