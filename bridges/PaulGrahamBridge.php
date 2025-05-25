<?php

class PaulGrahamBridge extends BridgeAbstract
{
    const NAME = 'Paul Graham Essays';
    const URI = 'https://www.paulgraham.com/articles.html';
    const DESCRIPTION = 'Returns the latest Paul Graham essays in display order';
    const MAINTAINER = 'Claire (for Stéphane)';
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

    // Navigate to the right TD
    // /html/body/table/tbody/tr/td[3]
        $tables = $html->find('body table');
        if (!isset($tables[0])) {
            return;
        }

        $tds = $tables[0]->find('td');
        if (!isset($tds[2])) {
            return;
        }

        $contentTd = $tds[2];

        // Find all inner tables (each one holds a single essay link)
        $essayTables = $contentTd->find('table');
        if (!isset($essayTables[1])) {
            return;
        }

        $essayTable = $essayTables[1];

    // /html/body/table/tbody/tr/td[3]/table[2]/tbody/tr[2]/td/font/a

        $links = $essayTable->find('font');

        $essayLinks = [];
        foreach ($links as $t) {
            $link = $t->find('a', 0);
            if (!$link) {
                continue;
            }

            $href = trim($link->href);
            $title = trim($link->plaintext);

            if (empty($href) || strpos($href, 'http') === 0 || !preg_match('/\.html$/', $href)) {
                continue;
            }

            $essayLinks[] = [
                'title' => $title,
                'url' => 'https://www.paulgraham.com/' . $href,
            ];
        }

        // Only fetch the first 10 (in display order)
        $essayLinks = array_slice($essayLinks, 0, 10);

        foreach ($essayLinks as $essay) {
            $item = [
                'uri' => $essay['url'],
                'title' => $essay['title'],
                'uid' => $essay['url'],
                'content' => '',
            ];

            $essayHtml = getSimpleHTMLDOMCached($essay['url']);
            if ($essayHtml) {
                $essayTables = $essayHtml->find('body table');
                if (isset($essayTables[0])) {
                    $essayTds = $essayTables[0]->find('td');
                    if (isset($essayTds[2])) {
                        $mainContent = $essayTds[2]->innertext;
                        $mainDom = str_get_html($mainContent);

                        // Strip unwanted layout elements
                        foreach ($mainDom->find('map, img, script') as $el) {
                            $el->outertext = '';
                        }

                        $item['content'] = $mainDom->save();
                    }
                }
            }

            $this->items[] = $item;
        }
    }
}

