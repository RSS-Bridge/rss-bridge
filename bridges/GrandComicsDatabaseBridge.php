<?php

class GrandComicsDatabaseBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting';
    const NAME = 'Grand Comics Database Bridge';
    const URI = 'https://www.comics.org/';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Returns the latest comics added to a series timeline';
    const PARAMETERS = [ [
        'series' => [
            'name' => 'Series id (from the timeline URL)',
            'required' => true,
            'exampleValue' => '63051',
        ],
    ]];

    public function collectData()
    {
        $url = self::URI . 'series/' . $this->getInput('series') . '/details/timeline/';
        $html = getSimpleHTMLDOM($url);

        $table = $html->find('table', 0);
        $list = array_reverse($table->find('[class^=row_even]'));
        $seriesName = $html->find('span[id=series_name]', 0)->innertext;

        // Get row headers
        $rowHeaders = $table->find('th');
        foreach ($list as $article) {
            // Skip empty rows
            $emptyRow = $article->find('td.empty_month');
            if (count($emptyRow) != 0) {
                continue;
            }

            $rows = $article->find('td');
            $key_date = $rows[0]->innertext;

            // Get URL too
            $uri = 'https://www.comics.org' . $article->find('a')[0]->href;

            // Build content
            $content = '';
            for ($i = 0; $i < count($rowHeaders); $i++) {
                $headerItem = $rowHeaders[$i]->innertext;
                $rowItem = $rows[$i]->innertext;
                $content = $content . $headerItem . ': ' . $rowItem . '<br/>';
            }

            // Build final item
            $content = str_replace('href="/', 'href="' . static::URI, $content);
            $item = [];
            $item['title'] = $seriesName . ' - ' . $key_date;
            $item['timestamp'] = strtotime($key_date);
            $item['content'] = str_get_html($content);
            $item['uri'] = $uri;

            $this->items[] = $item;
        }
    }
}
