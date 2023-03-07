<?php

/**********************************************
 * This bridge allows to get a RSS feed out of
 * Github's "Security Advisories" pages.
 *
 * Sometimes, vulnerabilities are posted on the
 * product repository before having a CVE ID
 * and being reported on CVEDetails.
 *
 * Page example : https://github.com/nextcloud/security-advisories/security/advisories
 *
 * ******************************************/

class GithubSecurityAdvisoriesBridge extends BridgeAbstract
{
    const NAME = 'Github Security Advisories';
	const MAINTAINER = 'ThibautPlg';
    const DESCRIPTION = 'Report Security Advisories of a Github repository';
    const URI = 'https://github.com';

    const PARAMETERS = [[
        'repository_url' => [
            'name' => 'Github repo Security Advisories URL',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'https://github.com/RSS-Bridge/rss-bridge/security/advisories',
        ],
    ]];

    private $DOM = null;

    // Main function, collect data and parse content
    public function collectData()
    {
        $re = '/https:\/\/github\.com\/.*?\/security-advisories\/security\/advisories/i';
        $url = trim($this->getInput('repository_url'));

        if (!preg_match($re, $url)) {
            returnClientError('Invalid URL. Must end in "/security-advisories\/security\/advisories"');
        }


        $this->DOM = getSimpleHTMLDOM($url);

        $advisories = $this->DOM->find('#advisories > .Box > div', 0);
        if ($advisories == null) {
            returnClientError('The Security advisory tab is not enabled on this repository');
        }

        foreach ($this->DOM->find('#advisories > .Box > div > ul > li') as $i => $tr) {

            $vulnUrl = "https://github.com/".$tr->find('.Link--primary', 0)->href;

            $details = $this->getDetails($vulnUrl);

            // Making a nice title
            $title = "[".$tr->find('.Label', 0)->title ."]";
            // If possible, add CVE id
            if(!!$details['CVEID']) {
                $title .= " ".$details['CVEID']." :";
            }
            // Adding real page title
            $title .= " " . $tr->find('.Link--primary', 0)->innertext;

            // Making the content, with the extended description if possible
            if(!!$details['extendedDescription']) {
                $content = $details['extendedDescription'];
            } else {
                $content = $tr->find('.Link--primary', 0)->innertext;
            }

            $this->items[] = [
                'uri' => $vulnUrl,
                'title' => $title,
                'timestamp' => $tr->find('relative-time', 0)->datetime,
                'content' => $content,
            ];
        }
    }

    // Returns an array of details found about the security advisory, if possible
    public function getDetails($vulnUrl)
    {
        $re = '/https:\/\/github\.com\/.*?\/security-advisories\/security\/advisories\/.*/i';
        $url = trim($vulnUrl);
        if (!preg_match($re, $url)) {
            returnServerError('Details cannot be fetched for this url : '.$url);
        }
        $DOM = getSimpleHTMLDOMCached($url);

        return [
            "extendedDescription" =>  $DOM->find('.markdown-body',0),
            "CVEID" => $DOM->find('.discussion-sidebar-item', 1)->find('div',0),
        ];
    }
}