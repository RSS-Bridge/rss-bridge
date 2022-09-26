<?php

class GoogleScholarBridge extends BridgeAbstract
{
    const NAME = 'Goolge Scholar';
    const URI = 'https://scholar.google.com/';
    const DESCRIPTION = 'Follow authors of scientific publications.';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 86400; // 24h

    const PARAMETERS = [[
        'userId' => [
            'name' => 'User ID',
            'exampleValue' => 'qc6CJjYAAAAJ',
            'required' => true
        ]
    ]];

    public function getIcon()
    {
        return 'https://scholar.google.com/favicon.ico';
    }

    public function collectData()
    {
        $uri = self::URI . '/citations?hl=en&view_op=list_works&sortby=pubdate&user=' . $this->getInput('userId');

        $html = getSimpleHTMLDOM($uri)
            or returnServerError('Could not fetch Google Scholar data.');

        $publications = $html->find('tr[class="gsc_a_tr"]');

        foreach ($publications as $publication) {
            $articleUrl = self::URI . htmlspecialchars_decode($publication->find('a[class="gsc_a_at"]', 0)->href);
            $articleTitle = $publication->find('a[class="gsc_a_at"]', 0)->plaintext;

            # fetch the article itself to extract rest of content
            $contentArticle = getSimpleHTMLDOMCached($articleUrl);
            $articleEntries = $contentArticle->find('div[class="gs_scl"]');

            $articleDate = '';
            $articleAbstract = '';
            $articleAuthor = '';
            $content = '';

            foreach ($articleEntries as $entry) {
                $field = $entry->find('div[class="gsc_oci_field"]', 0)->plaintext;
                $value = $entry->find('div[class="gsc_oci_value"]', 0)->plaintext;

                if ($field == 'Publication date') {
                    $articleDate = $value;
                } else if ($field == 'Description') {
                    $articleAbstract = $value;
                } else if ($field == 'Authors') {
                    $articleAuthor = $value;
                } else if ($field == 'Scholar articles' || $field == 'Total citations') {
                    continue;
                } else {
                    $content = $content . $field . ': ' . $value . '<br><br>';
                }
            }

            $content = $content . $articleAbstract;

            $item = [];

            $item['title'] = $articleTitle;
            $item['uri'] = $articleUrl;
            $item['timestamp'] = strtotime($articleDate);
            $item['author'] = $articleAuthor;
            $item['content'] = $content;

            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }
}
