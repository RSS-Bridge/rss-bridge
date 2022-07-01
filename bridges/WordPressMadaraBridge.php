<?php

/**
 * This bridge currently parses only chapter lists, but it can be further
 * extended to extract a list of manga titles using the implementation in this
 * project as a reference: https://github.com/manga-download/hakuneko
*/
class WordPressMadaraBridge extends BridgeAbstract
{
    const URI = 'https://live.mangabooth.com/';
    const NAME = 'WordPress Madara';
    const DESCRIPTION = 'Returns latest chapters published through the Madara Manga theme.
The default URI shows the Madara demo page.';
    const PARAMETERS = [
        'Manga Chapters' => [
            'url' => [
                'name' => 'Manga URL',
                'exampleValue' => 'https://live.mangabooth.com/manga/manga-text-chapter/',
                'required' => true
            ]
        ]
    ];

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Manga Chapters':
                $mangaInfo = $this->getMangaInfo($this->getInput('url'));
                return $mangaInfo['title'];
            default:
                return parent::getName();
        }
    }

    public function getURI()
    {
        return $this->getInput('url') ?? self::URI;
    }

    public function collectData()
    {
        $html = $this->queryAjaxChapters();

        // Check if the list subcategorizes by volume
        $volumes = $html->find('ul.volumns', 0);
        if ($volumes) {
            $this->parseVolumes($volumes);
        } else {
            $this->parseChapterList($html, null);
        }
    }

    protected function queryAjaxChaptersNew()
    {
        $uri = rtrim($this->getInput('url'), '/') . '/ajax/chapters/';
        $headers = [];
        $opts = [CURLOPT_POST => 1];
        return str_get_html(getContents($uri, $headers, $opts));
    }

    protected function queryAjaxChaptersOld()
    {
        $mangaInfo = $this->getMangaInfo($this->getInput('url'));
        $uri = rtrim($mangaInfo['root'], '/') . '/wp-admin/admin-ajax.php';
        $headers = [];
        $opts = [CURLOPT_POSTFIELDS => [
            'action' => 'manga_get_chapters',
            'manga' => $mangaInfo['id']
        ]];
        return str_get_html(getContents($uri, $headers, $opts));
    }

    protected function queryAjaxChapters()
    {
        $new = $this->queryAjaxChaptersNew();
        if ($new->find('.wp-manga-chapter')) {
            return $new;
        } else {
            return $this->queryAjaxChaptersOld();
        }
    }

    protected function parseVolumes($volumes)
    {
        foreach ($volumes->children(-1) as $volume) {
            $volume_name = trim($volume->find('a.has-child', 0)->plaintext);
            $this->parseChapterList($volume->find('ul', -1), $volume_name);
        }
    }

    protected function parseChapterList($chapters, $volume)
    {
        $mangaInfo = $this->getMangaInfo($this->getInput('url'));
        foreach ($chapters->find('li.wp-manga-chapter') as $chap) {
            $link = $chap->find('a', 0);

            $item = [];
            $item['title'] = ($volume ?? '') . ' ' . trim($link->plaintext);
            $item['uri'] = $link->href;
            $item['uid'] = $link->href;
            $item['timestamp'] = $chap->find('span.chapter-release-date', 0)->plaintext;
            $item['author'] = $mangaInfo['author'] ?? null;
            $item['categories'] = $mangaInfo['categories'] ?? null;
            $this->items[] = $item;
        }
    }

    /**
     * Retrieves manga info from cache or title page.
     * The returned array contains 'title', 'author', and 'categories' keys for use in feed items.
     * The 'id' key contains the manga title id, used for the old ajax api.
     * The 'root' key contains the website root.
     *
     * @param $url
     * @return array
     */
    protected function getMangaInfo($url)
    {
        $url_cache = 'TitleInfo_' . preg_replace('/[^\w]/', '.', rtrim($url, '/'));
        $cache = $this->loadCacheValue($url_cache);
        if (isset($cache)) {
            return $cache;
        }

        $info = [];
        $html = getSimpleHTMLDOMCached($url);

        $info['title'] = html_entity_decode($html->find('*[property=og:title]', 0)->content);
        $author = $html->find('.author-content', 0);
        if (!is_null($author)) {
            $info['author'] = trim($author->plaintext);
        }
        $cats = $html->find('.genres-content', 0);
        if (!is_null($cats)) {
            $info['categories'] = explode(', ', trim($cats->plaintext));
        }

        $info['id'] = $html->find('#manga-chapters-holder', 0)->getAttribute('data-id');
        // It's possible to find this from the input parameters, but it is already available here.
        $info['root'] = $html->find('a.logo', 0)->href;

        $this->saveCacheValue($url_cache, $info);
        return $info;
    }
}
