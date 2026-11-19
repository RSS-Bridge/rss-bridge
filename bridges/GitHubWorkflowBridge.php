<?php

declare(strict_types=1);

class GithubWorkflowBridge extends BridgeAbstract
{
    const NAME = 'GitHub Workflow';
    const URI = 'https://github.com/';
    const DESCRIPTION = 'Returns the status of the latest workflows for a GitHub repo.';
    const MAINTAINER = 'AMA147000';

    const PARAMETERS = [
        [
            'user' => [
                'name' => 'User',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'RSS-Bridge',
            ],
            'repo' => [
                'name' => 'Repository',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'rss-bridge',
            ],
            'workflow' => [
                'name' => 'Workflow file name',
                'type' => 'text',
                'required' => false,
                'exampleValue' => 'tests.yml',
            ],
        ],
    ];

    public function collectData()
    {
        $url = $this->getInput('workflow') ? sprintf(
            'https://github.com/%s/%s/actions/workflows/%s/',
            $this->getInput('user'),
            $this->getInput('repo'),
            $this->getInput('workflow'),
        ) : sprintf(
            'https://github.com/%s/%s/actions/',
            $this->getInput('user'),
            $this->getInput('repo'),
        );

        $dom = getSimpleHTMLDOM($url);

        foreach ($dom->find('div.Box-row') as $row) {
            $workflow_url = 'https://github.com' . trim($row->find('a.d-flex', 0)->href);
            $uid = basename($workflow_url);
            $name = trim($row->find('span.h4', 0)->plaintext);
            $workflow = trim($row->find('span.text-bold', 1)->plaintext);
            $initiator = trim($row->find('span.color-fg-muted', 1)->plaintext);
            $timestamp = trim($row->find('relative-time', 0)->datetime);

            if ($name === $workflow) {
                $name = '';
            } else {
                if ($this->getInput('workflow')) {
                    $name = "{$name} ";
                } else {
                    $name = "({$name}) ";
                }
            }

            // Fix for some workflows missing branch as they are configured to only run on one, which causes the script to crash
            $branch = $row->find('a.d-inline-block', 0);
            if ($branch) {
                $branch = trim($branch->plaintext);
                $branch = "(branch: {$branch}) ";
            } else {
                $branch = '';
            }

            $status_class = $row->find('svg.octicon', 0);
            $aria_label = $status_class ? $status_class->getAttribute('aria-label') : false;
            if (!$aria_label) {
                continue; // only triggered on non-final statuses anyways
            }
            $raw_status = trim($aria_label); // had to use that syntax instead of `->` because `aria-label` has a hyphen, which is apparently not valid in PHP
            switch ($raw_status) {
                case 'completed successfully:':
                    $status = 'succeeded';
                    break;
                case 'skipped:':
                    $status = 'skipped';
                    break;
                case 'failed:':
                    $status = 'failed';
                    break;
                case 'cancelled:': // I have no idea why GitHub uses the british spelling, but sure
                    $status = 'canceled';
                    break;
                default:
                    $status = '';
                    break;
            }
            // Filter out non-final statuses like 'importing' or 'starting'
            if (!$status) {
                continue;
            }

            $text = $row->find('span.d-block', 0)->plaintext;
            $start = strpos($text, '#');
            $end = strpos($text, ':', $start);
            $run_number = substr($text, $start, $end - $start); // keep the leading `#` as I was gonna add it anyways

            $content_dom = getSimpleHTMLDOMCached($workflow_url, 86400);
            // Fix relative links
            foreach ($content_dom->find('a') as $link) {
                $href = $link->href;

                if (strpos($href, '/') === 0 && strpos($href, '//') !== 0) {
                    $link->href = 'https://github.com' . $href;
                }
            }
            $all_content = $content_dom->find(
                '.tmp-pb-5.flex-auto > div.js-socket-channel.js-updatable-content',
            );
            $content = $all_content[0];
            $annotations = $all_content[4];
            // Remove the buttons
            foreach ($annotations->find('button') as $button) {
                $button->outertext = '';
            }
            $content .= $annotations;

            $item = [];
            $item['uri'] = $workflow_url;
            $item['title'] = $this->getInput('workflow') ? "{$name}{$branch}{$run_number} {$status}" : "{$workflow} {$name}{$branch}{$run_number} {$status}";
            $item['timestamp'] = $timestamp;
            $item['author'] = $initiator;
            $item['content'] = $content;
            $item['categories'] = $this->getInput('workflow') ? [$branch, $initiator, $status] : [$workflow, $branch, $initiator, $status];
            $item['uid'] = $uid;
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if ($this->getInput('user') && $this->getInput('repo')) {
            if ($this->getInput('workflow')) {
                return sprintf(
                    'GitHub Workflow - %s/%s/%s',
                    $this->getInput('user'),
                    $this->getInput('repo'),
                    $this->getInput('workflow'),
                );
            }
            return sprintf(
                'GitHub Workflows - %s/%s',
                $this->getInput('user'),
                $this->getInput('repo'),
            );
        }
        return self::NAME;
    }

    public function getURI()
    {
        if ($this->getInput('user') && $this->getInput('repo')) {
            if ($this->getInput('workflow')) {
                return sprintf(
                    'https://github.com/%s/%s/actions/workflows/%s/',
                    $this->getInput('user'),
                    $this->getInput('repo'),
                    $this->getInput('workflow'),
                );
            }
            return sprintf(
                'https://github.com/%s/%s/actions/',
                $this->getInput('user'),
                $this->getInput('repo'),
            );
        }
        return self::URI;
    }

    public function getIcon()
    {
        return 'https://github.githubassets.com/favicons/favicon.svg';
    }
}
