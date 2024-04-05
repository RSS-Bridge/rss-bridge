<?php

class FDroidRepoBridge extends BridgeAbstract
{
    const NAME = 'F-Droid Repository Bridge';
    const URI = 'https://f-droid.org/';
    const DESCRIPTION = 'Query any F-Droid Repository for its latest updates.';

    const ITEM_LIMIT = 50;

    const PARAMETERS = [
        'global' => [
            'url' => [
                'name' => 'Repository URL',
                'title' => 'Usually ends with /repo/',
                'required' => true,
                'exampleValue' => 'https://molly.im/fdroid/foss/fdroid/repo'
            ]
        ],
        'Latest Updates' => [
            'sorting' => [
                'name' => 'Sort By',
                'type' => 'list',
                'values' => [
                    'Latest added apps' => 'added',
                    'Latest updated apps' => 'lastUpdated'
                ]
            ],
            'locale' => [
                'name' => 'Locale',
                'defaultValue' => 'en-US'
            ]
        ],
        'Follow Package' => [
            'package' => [
                'name' => 'Package Identifier',
                'required' => true,
                'exampleValue' => 'im.molly.app'
            ]
        ]
    ];

    // Stores repo information
    private $repo;

    public function collectData()
    {
        $this->repo = $this->fetchData();
        switch ($this->queriedContext) {
            case 'Latest Updates':
                $this->getAllUpdates();
                break;
            case 'Follow Package':
                $this->getPackage($this->getInput('package'));
                break;
            default:
                throw new \Exception('Unimplemented Context (collectData)');
        }
    }

    private function fetchData()
    {
        $url = $this->getURI();
        $json = getContents($url . '/index-v1.json');
        $data = Json::decode($json);
        return $data;
    }

    private function getAllUpdates()
    {
        $apps = $this->repo['apps'];
        usort($apps, function ($a, $b) {
            return $b[$this->getInput('sorting')] <=> $a[$this->getInput('sorting')];
        });
        $apps = array_slice($apps, 0, self::ITEM_LIMIT);
        foreach ($apps as $app) {
            $latest = reset($this->repo['packages'][$app['packageName']]);

            if (isset($app['localized'])) {
                // Try provided locale, then en-US, then any
                $lang = $app['localized'];
                $lang = $lang[$this->getInput('locale')] ?? $lang['en-US'] ?? reset($lang);
            } else {
                $lang = [];
            }

            $item = [];
            $item['uri'] = $this->getURI() . '/' . $latest['apkName'];
            $item['title'] = $lang['name'] ?? $app['packageName'];
            $item['title'] .= ' ' . $latest['versionName'];
            $item['timestamp'] = date(DateTime::ISO8601, (int) ($app['lastUpdated'] / 1000));
            if (isset($app['authorName'])) {
                $item['author'] = $app['authorName'];
            }
            if (isset($app['categories'])) {
                $item['categories'] = $app['categories'];
            }

            // Adding Content
            $icon = $app['icon'] ?? '';
            if (!empty($icon)) {
                $icon = $this->getURI() . '/icons-320/' . $icon;
                $item['enclosures'] = [$icon];
                $icon = '<img src="' . $icon . '">';
            }
            $summary = $lang['summary'] ?? $app['summary'] ?? '';
            $description = markdownToHtml(trim($lang['description'] ?? $app['description'] ?? 'None'));
            $whatsNew = markdownToHtml(trim($lang['whatsNew'] ?? 'None'));
            $website = $this->createAnchor($lang['webSite'] ?? $app['webSite'] ?? $app['authorWebSite'] ?? null);
            $source = $this->createAnchor($app['sourceCode'] ?? null);
            $issueTracker = $this->createAnchor($app['issueTracker'] ?? null);
            $license = $app['license'] ?? 'None';
            $item['content'] = <<<EOD
{$icon}
<p>{$summary}</p>
<h1>Description</h1>
{$description}
<h1>What's New</h1>
{$whatsNew}
<h1>Information</h1>
<p>Website: {$website}</p>
<p>Source Code: {$source}</p>
<p>Issue Tracker: {$issueTracker}</p>
<p>license: {$app['license']}</p>
EOD;
            $this->items[] = $item;
        }
    }

    private function getPackage($package)
    {
        if (!isset($this->repo['packages'][$package])) {
            throw new \Exception('Invalid Package Name');
        }
        $package = $this->repo['packages'][$package];

        $count = self::ITEM_LIMIT;
        foreach ($package as $version) {
            $item = [];
            $item['uri'] = $this->getURI() . '/' . $version['apkName'];
            $item['title'] = $version['versionName'];
            $item['timestamp'] = date(DateTime::ISO8601, (int) ($version['added'] / 1000));
            $item['uid'] = (string) $version['versionCode'];
            $size = round($version['size'] / 1048576, 1); // Bytes -> MB
            $sdk_link = 'https://developer.android.com/studio/releases/platforms';
            $item['content'] = <<<EOD
<p>size: {$size}MB</p>
<p>Minimum SDK: {$version['minSdkVersion']}
(<a href="{$sdk_link}">SDK to Android Version List</a>)</p>
<p>hash ({$version['hashType']}): {$version['hash']}</p>
EOD;
            $this->items[] = $item;
            if (--$count <= 0) {
                break;
            }
        }
    }

    public function getURI()
    {
        if (empty($this->queriedContext)) {
            return parent::getURI();
        }

        $url = rtrim($this->getInput('url'), '/');
        if (strstr($url, '?', true)) {
            return strstr($url, '?', true);
        } else {
            return $url;
        }
    }

    public function getName()
    {
        if (empty($this->queriedContext)) {
            return parent::getName();
        }

        $name = $this->repo['repo']['name'];
        switch ($this->queriedContext) {
            case 'Latest Updates':
                return $name;
            case 'Follow Package':
                return $this->getInput('package') . ' - ' . $name;
            default:
                throw new \Exception('Unimplemented Context (getName)');
        }
    }

    private function createAnchor($url)
    {
        if (empty($url)) {
            return null;
        }
        return sprintf('<a href="%s">%s</a>', $url, $url);
    }
}
