<?php

class FirefoxAddonsBridge extends BridgeAbstract
{
    const NAME = 'Firefox Add-ons Bridge';
    const URI = 'https://addons.mozilla.org/';
    const DESCRIPTION = 'Returns version history for a Firefox Add-on.';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [[
            'id' => [
                'name' => 'Add-on ID',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'save-to-the-wayback-machine',
            ]
        ]
    ];

    const CACHE_TIMEOUT = 3600;

    private $feedName = '';

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $this->feedName = $html->find('h1[class="AddonTitle"] > a', 0)->innertext;
        $author = $html->find('span.AddonTitle-author > a', 0)->plaintext;

        foreach ($html->find('li.AddonVersionCard') as $li) {
            $item = [];

            $item['title'] = $li->find('h2.AddonVersionCard-version', 0)->plaintext;
            $item['uid'] = $item['title'];
            $item['uri'] = $this->getURI();
            $item['author'] = $author;

            $releaseDateRegex = '/Released ([\w, ]+) - ([\w. ]+)/';
            if (preg_match($releaseDateRegex, $li->find('div.AddonVersionCard-fileInfo', 0)->plaintext, $match)) {
                $item['timestamp'] = $match[1];
                $size = $match[2];
            }

            $compatibility = $li->find('div.AddonVersionCard-compatibility', 0)->plaintext;
            $license = $li->find('p.AddonVersionCard-license', 0)->innertext;

            if ($li->find('a.InstallButtonWrapper-download-link', 0)) {
                $downloadlink = $li->find('a.InstallButtonWrapper-download-link', 0)->href;
            } elseif ($li->find('a.Button.Button--action.AMInstallButton-button.Button--puffy', 0)) {
                $downloadlink = $li->find('a.Button.Button--action.AMInstallButton-button.Button--puffy', 0)->href;
            }

            $releaseNotes = $this->removeLinkRedirects($li->find('div.AddonVersionCard-releaseNotes', 0));

            $xpiFileRegex = '/([A-Za-z0-9_.-]+)\.xpi$/';
            if (preg_match($xpiFileRegex, $downloadlink, $match)) {
                $xpiFilename = $match[0];
            }

            $item['content'] = <<<EOD
<strong>Release Notes</strong>
<p>{$releaseNotes}</p>
<strong>Compatibility</strong>
<p>{$compatibility}</p>
<strong>License</strong>
<p>{$license}</p>
<strong>Download</strong>
<p><a href="{$downloadlink}">{$xpiFilename}</a> ($size)</p>
EOD;

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('id'))) {
            return self::URI . 'en-US/firefox/addon/' . $this->getInput('id') . '/versions/';
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName . ' - Firefox Add-on';
        }

        return parent::getName();
    }

    /**
     * Removes 'https://prod.outgoing.prod.webservices.mozgcp.net/v1/' from external links
     */
    private function removeLinkRedirects($html)
    {
        $outgoingRegex = '/https:\/\/prod.outgoing\.prod\.webservices\.mozgcp\.net\/v1\/(?:[A-z0-9]+)\//';
        foreach ($html->find('a') as $a) {
            $a->href = urldecode(preg_replace($outgoingRegex, '', $a->href));
        }

        return $html->innertext;
    }

    public function detectParameters($url)
    {
        $params = [];

        // Example: https://addons.mozilla.org/en-US/firefox/addon/ublock-origin
        $pattern = '/addons\.mozilla\.org\/(?:[\w-]+\/)?firefox\/addon\/([\w-]+)/';
        if (preg_match($pattern, $url, $matches)) {
            $params['id'] = $matches[1];
            return $params;
        }

        return null;
    }
}
