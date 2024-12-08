<?php

class UnraidCommunityApplicationsBridge extends BridgeAbstract
{
    const NAME = 'Unraid Community Applications';
    const URI = 'https://forums.unraid.net/topic/38582-plug-in-community-applications/';
    const DESCRIPTION = 'Fetches the latest fifteen new apps/plugins from Unraid Community Applications';
    const MAINTAINER = 'Paroleen';
    const CACHE_TIMEOUT = 3600;

    const APPSURI = 'https://raw.githubusercontent.com/Squidly271/AppFeed/master/applicationFeed.json';

    private $apps = [];

    private function fetchApps()
    {
        $this->apps = getContents(self::APPSURI);
        $this->apps = json_decode($this->apps, true)['applist'];
    }

    private function sortApps()
    {
        usort($this->apps, function ($app1, $app2) {
            return $app1['FirstSeen'] < $app2['FirstSeen'] ? 1 : -1;
        });
    }

    public function collectData()
    {
        $this->fetchApps();
        $this->sortApps();
        foreach ($this->apps as $app) {
            if (array_key_exists('Language', $app)) {
                continue;
            }
            $item = [];
            $item['title'] = $app['Name'];
            $item['timestamp'] = $app['FirstSeen'];
            $item['author'] = explode('\'', $app['Repo'])[0];
            $item['content'] = '';

            if (isset($app['CategoryList'])) {
                $item['categories'] = $app['CategoryList'];
            }

            if (array_key_exists('Icon', $app)) {
                $item['content'] .= '<img style="width: 64px" src="'
                    . $app['Icon']
                    . '">';
            }

            if (array_key_exists('Overview', $app)) {
                $item['content'] .= '<p>'
                    . $app['Overview']
                    . '</p>';
            }

            if (array_key_exists('Project', $app)) {
                $item['uri'] = $app['Project'];
            }

            if (array_key_exists('Registry', $app)) {
                $item['content'] .= '<br><a href="'
                    . $app['Registry']
                    . '">Docker Hub</a>';
            }

            if (array_key_exists('Support', $app)) {
                $item['content'] .= '<br><a href="'
                    . $app['Support']
                    . '">Support</a>';
            }

            $this->items[] = $item;

            if (count($this->items) >= 150) {
                break;
            }
        }
    }
}
