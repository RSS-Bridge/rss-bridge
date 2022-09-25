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
                'exampleValue' => 'https://srv.tt-rss.org/fdroid/repo'
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
                'exampleValue' => 'org.fox.ttrss'
            ]
        ]
    ];

    // Stores repo information
    private $repo;

    public function collectData()
    {
        if (!extension_loaded('zip')) {
            throw new \Exception('FDroidRepoBridge requires the php-zip extension');
        }

        $this->repo = $this->getRepo();
        switch ($this->queriedContext) {
            case 'Latest Updates':
                $this->getAllUpdates();
                break;
            case 'Follow Package':
                $this->getPackage($this->getInput('package'));
                break;
            default:
                returnServerError('Unimplemented Context (collectData)');
        }
    }

    public function getURI()
    {
        if (empty($this->queriedContext)) {
            return parent::getURI();
        }

        $url = rtrim($this->GetInput('url'), '/');
        return strstr($url, '?', true) ?: $url;
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
                returnServerError('Unimplemented Context (getName)');
        }
    }

    private function getRepo()
    {
        $url = $this->getURI();

        // Get repo information (only available as JAR)
        $jar = getContents($url . '/index-v1.jar');
        $jar_loc = tempnam(sys_get_temp_dir(), '');
        file_put_contents($jar_loc, $jar);

        // JAR files are specially formatted ZIP files
        $jar = new \ZipArchive();
        if ($jar->open($jar_loc) !== true) {
            unlink($jar_loc);
            throw new \Exception('Failed to extract archive');
        }

        // Get file pointer to the relevant JSON inside
        $fp = $jar->getStream('index-v1.json');
        if (!$fp) {
            returnServerError('Failed to get file pointer');
        }

        $data = json_decode(stream_get_contents($fp), true);
        fclose($fp);
        $jar->close();
        unlink($jar_loc);
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
            $website = $this->link($lang['webSite'] ?? $app['webSite'] ?? $app['authorWebSite'] ?? null);
            $source = $this->link($app['sourceCode'] ?? null);
            $issueTracker = $this->link($app['issueTracker'] ?? null);
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
            returnClientError('Invalid Package Name');
        }
        $package = $this->repo['packages'][$package];

        $count = self::ITEM_LIMIT;
        foreach ($package as $version) {
            $item = [];
            $item['uri'] = $this->getURI() . '/' . $version['apkName'];
            $item['title'] = $version['versionName'];
            $item['timestamp'] = date(DateTime::ISO8601, (int) ($version['added'] / 1000));
            $item['uid'] = $version['versionCode'];
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

    private function link($url)
    {
        if (empty($url)) {
            return null;
        }
        return '<a href="' . $url . '">' . $url . '</a>';
    }
}
