<?php

class GoodreadsBridge extends BridgeAbstract
{
    const MAINTAINER = 'captn3m0';
    const NAME = 'Goodreads Bridge';
    const URI = 'https://www.goodreads.com/';
    const CACHE_TIMEOUT = 0; // 30min
    const DESCRIPTION = 'Various RSS feeds from Goodreads';

    const CONTEXT_AUTHOR_BOOKS = 'Books by Author';

    // Using a specific context because I plan to expand this soon
    const PARAMETERS = [
        'Books by Author' => [
            'author_url' => [
                'name' => 'Link to author\'s page on Goodreads',
                'type' => 'text',
                'required' => true,
                'title' => 'Should look somewhat like goodreads.com/author/show/',
                'pattern' => '^(https:\/\/)?(www.)?goodreads\.com\/author\/show\/\d+\..*$',
                'exampleValue' => 'https://www.goodreads.com/author/show/38550.Brandon_Sanderson'
            ],
            'published_only' => [
                'name' => 'Show published books only',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'If left unchecked, this will return unpublished books as well',
                'defaultValue' => 'checked',
            ],
        ],
    ];

    private function collectAuthorBooks($url)
    {
        $regex = '/goodreads\.com\/author\/show\/(\d+)/';

        preg_match($regex, $url, $matches);

        $authorId = $matches[1];

        $authorListUrl = "https://www.goodreads.com/author/list/$authorId?sort=original_publication_year";

        $html = getSimpleHTMLDOMCached($authorListUrl, self::CACHE_TIMEOUT);

        foreach ($html->find('tr[itemtype="http://schema.org/Book"]') as $row) {
            $dateSpan = $row->find('.uitext', 0)->plaintext;
            $date = null;

            // If book is not yet published, ignore for now
            if (preg_match('/published\s+(\d{4})/', $dateSpan, $matches) === 1) {
                // Goodreads doesn't give us exact publication date here, only a year
                // We are skipping future dates anyway, so this is def published
                // but we can't pick a dynamic date either to keep clients from getting
                // confused. So we pick a guaranteed date of 1st-Jan instead.
                $date = $matches[1] . '-01-01';
            } elseif ($this->getInput('published_only') !== 'checked') {
                // We can return unpublished books as well
                $date = date('Y-01-01');
            } else {
                continue;
            }

            $row = defaultLinkTo($row, $this->getURI());

            $item['title'] = $row->find('.bookTitle', 0)->plaintext;
            $item['uri'] = $row->find('.bookTitle', 0)->getAttribute('href');
            $item['author'] = $row->find('.authorName', 0)->plaintext;
            $item['content'] = '<a href="'
            . $row->find('.bookTitle', 0)->getAttribute('href')
            . '"><img src="'
            . $row->find('.bookCover', 0)->getAttribute('src')
            . '"></a>';
            $item['timestamp'] = $date;
            $item['enclosures'] = [
            $row->find('.bookCover', 0)->getAttribute('src')
            ];

            $this->items[] = $item; // Add item to the list
        }
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_AUTHOR_BOOKS:
                $this->collectAuthorBooks($this->getInput('author_url'));
                break;

            default:
                throw new Exception('Invalid context', 1);
            break;
        }
    }
}
