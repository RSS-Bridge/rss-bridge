<?php

declare(strict_types=1);

class ProjectMuseBridge extends BridgeAbstract
{
    const NAME          = 'Project Muse';
    const URI           = 'https://muse.jhu.edu';
    const DESCRIPTION   = 'Returns the latest articles from a selected journal';
    const MAINTAINER    = 'tillcash';
    const CACHE_TIMEOUT = 43200; // 12 hours

    const PARAMETERS = [
        [
            'journal' => [
                'name' => 'Journal',
                'type' => 'list',
                'values' => [
                    'Technology and Culture' => '194',
                ]
            ]
        ]
    ];

    public function collectData()
    {
        $journalId = $this->getInput('journal');
        $url = self::URI . '/journal/' . $journalId;

        $html = getSimpleHTMLDOMCached($url, self::CACHE_TIMEOUT);

        $latestVolumeGroup = $html->find('div.vol_group', 0);

        foreach ($latestVolumeGroup->find('.volume') as $issue) {
            $issueLink = $issue->find('a', 0);
            if (!$issueLink) {
                continue;
            }

            $issueUrl = urljoin(self::URI, $issueLink->href);
            $issueTitle = trim($issueLink->plaintext);

            $issueHtml = getSimpleHTMLDOMCached($issueUrl, self::CACHE_TIMEOUT);

            foreach ($issueHtml->find('#articles_list_wrap .card_text') as $article) {
                $titleLink = $article->find('.title h3 a', 0);
                if (!$titleLink) {
                    continue;
                }

                $item = [];

                $fullTitle = trim($titleLink->plaintext) . ' | ' . $issueTitle;
                $item['title'] = html_entity_decode($fullTitle, ENT_QUOTES, 'UTF-8');

                $item['uri'] = urljoin(self::URI, $titleLink->href);
                $item['uid'] = $item['uri'];

                $author = $article->find('.author span', 0);
                if ($author && !empty(trim($author->plaintext))) {
                    $item['author'] = trim($author->plaintext);
                }

                $doi = $article->find('.doi', 0);
                $pg = $article->find('.pg', 0);

                $content = '';
                if ($doi) {
                    $content .= $doi->outertext;
                }
                if ($pg) {
                    $content .= $pg->plaintext;
                }

                $item['content'] = $content;

                $this->items[] = $item;
            }
        }
    }

    public function getName()
    {
        $journalName = $this->getKey('journal');
        if ($journalName) {
            return 'Project MUSE: ' . $journalName;
        }
        return parent::getName();
    }
}
