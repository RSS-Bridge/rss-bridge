<?php

declare(strict_types=1);

class COPRBridge extends BridgeAbstract
{
    const NAME = 'COPR';
    const URI = 'https://copr.fedorainfracloud.org/';
    const DESCRIPTION = 'Returns the status of the latest builds for a COPR project.';
    const MAINTAINER = 'AMA147000';

    const PARAMETERS = [
        [
            'user' => [
                'name' => 'User',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'ama1470',
            ],
            'project' => [
                'name' => 'Project',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'kwin-effects-glass',
            ],
        ],
    ];

    public function collectData()
    {
        $url = sprintf(
            'https://copr.fedorainfracloud.org/coprs/%s/%s/builds/',
            $this->getInput('user'),
            $this->getInput('project'),
        );

        $dom = getSimpleHTMLDOM($url);

        foreach ($dom->find('tr.build-row') as $row) {
            $columns = $row->find('td');

            $build_id = trim($columns[0]->plaintext);
            $build_url = sprintf(
                'https://copr.fedorainfracloud.org/coprs/%s/%s/build/%s/',
                $this->getInput('user'),
                $this->getInput('project'),
                $build_id,
            );
            $package = trim($columns[1]->plaintext);
            $version = trim($columns[2]->plaintext);
            $timestamp = trim($columns[3]->title);
            $status = trim($columns[5]->plaintext);

            // Filter out non-final statuses like 'importing' or 'starting'
            $valid_statuses = [
                'succeeded',
                'forked',
                'skipped',
                'failed',
                'canceled',
            ];
            // Side note: `needle` and `haystack` are just perfect input names lmao
            if (!in_array($status, $valid_statuses)) {
                continue;
            }

            $content_dom = getSimpleHTMLDOMCached($build_url, 86400);
            // Fix relative links
            foreach ($content_dom->find('a') as $link) {
                $href = $link->href;

                if (strpos($href, '/') === 0 && strpos($href, '//') !== 0) {
                    $link->href = 'https://copr.fedorainfracloud.org' . $href;
                }
            }
            $content = $content_dom->find('#content > .container > .row');
            $content = implode("\n", $content);

            $item = [];
            $item['uri'] = $build_url;
            $item['title'] = "{$package} v{$version} {$status}";
            $item['timestamp'] = $timestamp;
            $item['author'] = $this->getInput('user');
            $item['content'] = $content;
            $item['categories'] = [$package, $status];
            $item['uid'] = $build_id;
            $this->items[] = $item;
        }

        usort($this->items, function ($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });
    }

    public function getName()
    {
        if ($this->getInput('user') && $this->getInput('project')) {
            return sprintf(
                'COPR Builds - %s/%s',
                $this->getInput('user'),
                $this->getInput('project'),
            );
        }
        return self::NAME;
    }

    public function getURI()
    {
        if ($this->getInput('user') && $this->getInput('project')) {
            return sprintf(
                'https://copr.fedorainfracloud.org/coprs/%s/%s/',
                $this->getInput('user'),
                $this->getInput('project'),
            );
        }
        return self::URI;
    }

    public function getIcon()
    {
        return 'https://copr.fedorainfracloud.org/static/favicon.ico';
    }
}
