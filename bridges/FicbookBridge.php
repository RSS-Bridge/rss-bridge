<?php

class FicbookBridge extends BridgeAbstract
{
    const NAME = 'Ficbook Bridge';
    const URI = 'https://ficbook.net/';
    const DESCRIPTION = 'No description provided';
    const MAINTAINER = 'logmanoriginal';

    const PARAMETERS = [
        'Site News' => [],
        'Fiction Updates' => [
            'fiction_id' => [
                'name' => 'Fanfiction ID',
                'type' => 'text',
                'pattern' => '[0-9]+',
                'required' => true,
                'title' => 'Insert fanfiction ID',
                'exampleValue' => '5783919',
            ],
            'include_contents' => [
                'name' => 'Include contents',
                'type' => 'checkbox',
                'title' => 'Activate to include contents in the feed',
            ],
        ],
        'Fiction Comments' => [
            'fiction_id' => [
                'name' => 'Fanfiction ID',
                'type' => 'text',
                'pattern' => '[0-9]+',
                'required' => true,
                'title' => 'Insert fanfiction ID',
                'exampleValue' => '5783919',
            ],
        ],
    ];

    protected $titleName;

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Site News':
                // For some reason this is not HTTPS
                return 'http://ficbook.net/sitenews';

            case 'Fiction Updates':
                return self::URI
                . 'readfic/'
                . urlencode($this->getInput('fiction_id'));

            case 'Fiction Comments':
                return self::URI
                . 'readfic/'
                . urlencode($this->getInput('fiction_id'))
                . '/comments#content';

            default:
                return parent::getURI();
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Site News':
                return $this->queriedContext . ' | ' . self::NAME;

            case 'Fiction Updates':
                return $this->titleName . ' | ' . self::NAME;

            case 'Fiction Comments':
                return $this->titleName . ' | Comments | ' . self::NAME;

            default:
                return self::NAME;
        }
    }

    public function collectData()
    {
        $header = ['Accept-Language: en-US'];

        $html = getSimpleHTMLDOM($this->getURI(), $header);

        $html = defaultLinkTo($html, self::URI);

        if ($this->queriedContext == 'Fiction Updates' or $this->queriedContext == 'Fiction Comments') {
            $this->titleName = $html->find('.fanfic-main-info > h1', 0)->innertext;
        }

        switch ($this->queriedContext) {
            case 'Site News':
                return $this->collectSiteNews($html);
            case 'Fiction Updates':
                return $this->collectUpdatesData($html);
            case 'Fiction Comments':
                return $this->collectCommentsData($html);
        }
    }

    private function collectSiteNews($html)
    {
        foreach ($html->find('.news_view') as $news) {
            $this->items[] = [
                'title' => $news->find('h1.title', 0)->plaintext,
                'timestamp' => strtotime($this->fixDate($news->find('span[title]', 0)->title)),
                'content' => $news->find('.news_text', 0),
            ];
        }
    }

    private function collectCommentsData($html)
    {
        foreach ($html->find('article.comment-container') as $article) {
            $this->items[] = [
                'uri' => $article->find('.comment_link_to_fic > a', 0)->href,
                'title' => $article->find('.comment_author', 0)->plaintext,
                'author' => $article->find('.comment_author', 0)->plaintext,
                'timestamp' => strtotime($this->fixDate($article->find('time[datetime]', 0)->datetime)),
                'content' => $article->find('.comment_message', 0),
                'enclosures' => [$article->find('img', 0)->src],
            ];
        }
    }

    private function collectUpdatesData($html)
    {
        foreach ($html->find('ul.list-of-fanfic-parts > li') as $chapter) {
            $item = [
                'uri' => $chapter->find('a', 0)->href,
                'title' => $chapter->find('a', 0)->plaintext,
                'timestamp' => strtotime($this->fixDate($chapter->find('span[title]', 0)->title)),
            ];

            if ($this->getInput('include_contents')) {
                $content = getSimpleHTMLDOMCached($item['uri']);
                $item['content'] = $content->find('#content', 0);
            }

            $this->items[] = $item;

            // Sort by time, descending
            usort($this->items, function ($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
        }
    }

    private function fixDate($date)
    {
        // FIXME: This list was generated using Google tranlator. Someone who
        // actually knows russian should check this list! Please keep in mind
        // that month names must match exactly the names returned by Ficbook.
        $ru_month = [
            'января',
            'февраля',
            'марта',
            'апреля',
            'мая',
            'июня',
            'июля',
            'августа',
            'сентября',
            'октября',
            'ноября',
            'декабря',
        ];

        $en_month = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];

        $fixed_date = str_replace($ru_month, $en_month, $date);

        if ($fixed_date === $date) {
            Debug::log('Unable to fix date: ' . $date);
            return null;
        }

        return $fixed_date;
    }
}
