<?php

class Mailman2Bridge extends BridgeAbstract
{

    const NAME = 'Mailman2Bridge';
    const URI = 'https://github.com/rss-bridge/rss-bridge';
    const MAINTAINER = 'imagoiq';
    const CACHE_TIMEOUT = 0; // 30min
    const DESCRIPTION = 'Parse any mailman mailing lists';

    const PARAMETERS = [
        '' => [
            'index_url' => [
                'name' => 'Enter web archive URL',
                'title' => <<<"EOL"
                            Specify the URL from the archive page where all the archive are listed by month.
                            EOL
                , 'type' => 'text',
                'exampleValue' => 'http://example.com/pipermail/name/',
                'required' => true
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'title' => 'Maximum number of items to return',
                'defaultValue' => 5,
            ],
        ],
    ];

    public function collectData()
    {
        $mails = [];

        // Retrieve inputs

        $limit = $this->getInput('limit');
        $indexUrl = $this->getInput('index_url');

        // Get all mails URLs and contents

        $monthUrls = $this->getMonthArchiveUrls($indexUrl);

        foreach ($monthUrls as $monthUrl) {
            if (!$monthUrl['bydate']) {
                continue;
            }

            $monthMailsUrls = $this->getMonthMailsUrls($monthUrl['bydate']);
            $monthMailsContent = $this->getMonthMailsContent($monthUrl['download']);

            // Match Urls with contents from txt files. Urls cannot be reconstructed from the txt content.

            foreach ($monthMailsContent as $index => $content) {
                $mail = [
                    'url' => $monthMailsUrls[$index],
                    'content' => $content
                ];

                array_push($mails, $mail);
            }

            if (count($mails) > $limit) {
                break;
            }
        }

        // Trim URLs over the limit

        if (count($mails) > $limit) {
            $mails = array_slice($mails, 0, $limit);
        }

        // Create item for each mail

        foreach ($mails as $mail) {
            $item = [];

            $item['uid'] = $this->getMailMeta('Message-ID', $mail['content']);
            $item['uri'] = $mail['url'];
            $item['title'] = $this->getMailMeta('Subject', $mail['content']);
            $item['author'] = $this->formatMailMetaAuthor($this->getMailMeta('From', $mail['content']));
            $item['timestamp'] = $this->getMailMeta('Date', $mail['content']);
            $item['content'] = $this->formatContent($mail['content']);

            $this->items[] = $item;
        }
    }

    private function getMailMeta($name, $post)
    {
        preg_match('/(?<=' . $name . ':).*$/m', $post, $matches);
        return isset($matches[0]) ? trim($matches[0]) : null;
    }

    private function formatMailMetaAuthor($author)
    {
        return preg_replace('/\sat\s/', '@', $author);
    }

    private function formatContent($content)
    {
        $formatter = new Formatter();
        return nl2br(htmlspecialchars_decode(htmlentities(html_entity_decode($formatter->render($content)))));
    }

    /**
     * Get all the mails content giving a month url
     *
     * @param string url Page for a month
     * @return array
     */
    private function getMonthMailsContent($monthArchiveUrl)
    {
        $fileContent = file_get_contents($monthArchiveUrl);
        $decodedFileContent = str_ends_with($monthArchiveUrl, '.gz') ? gzdecode($fileContent) : $fileContent;
        $mails = explode('From ', $decodedFileContent); // next part could be used, but it seems to not be consistently added after each message.

        $isNotMail = function ($mail) {
            return $this->getMailMeta('From', $mail);
        };

        return array_reverse(array_filter($mails, $isNotMail));
    }

    /**
     *  Get all the mails URLs giving a month url
     *
     * @param string url Page for a month
     * @return array
     */
    private function getMonthMailsUrls($url)
    {
        $html = getSimpleHTMLDOMCached($url, 1800);
        $parent_folder = pathinfo($url, PATHINFO_DIRNAME) . '/';
        $html = defaultLinkTo($html, $parent_folder);

        $links = array_map(fn ($val) => $val->getAttribute('href'), $html->find('ul', 1)->find('li a[href$=".html"]'));

        return array_reverse($links);
    }

    /**
     * Get all the month txt gzip archive URLs
     *
     * @param string url Index page of archive
     * @return array
     */
    private function getMonthArchiveUrls($url)
    {
        $html = getSimpleHTMLDOMCached($url, 1800);
        $html = defaultLinkTo($html, $url);

        $func = function ($tr) {
            $monthUrl = $tr->find('a[href$="date.html"]', 0);
            $downloadUrl = $tr->find('a[href$=".txt"], a[href$=".txt.gz"]', 0);

            return [
                'bydate' => $monthUrl ? $monthUrl->getAttribute('href') : null ,
                'download' => $downloadUrl ? $downloadUrl->getAttribute('href') : null
            ];
        };

        return array_map($func, $html->find('tr'));
    }
}

// Based on https://gist.github.com/jbroadway/2836900

class Formatter // phpcs:ignore
{
    public static $rules = [
        '/[\s\S]*?^Message-ID:[\s\S]*?>\n\n/m' => '', // Metadata
        '/-+\s+next part\s+-+[\s\S]+?(?=^$|\Z)/m' => '', // next part
        '/\[([^\[]+)\]\(([^\)]+)\)/' => '<a href="$2">$1</a>',  // links
        '/(?<!href=[\'"])https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.
        [a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()!@:%_\+.,~#?&\/\/=]*)/' => '<a href="$0">$0</a>', // links
        '/(\w+) <(.*) at (.*)>/' => '<a href="mailto:$2@$3">$1</a>', // emails
        '/(\*|\*\*|__)(.*?)\1/' => '<strong>\2</strong>', // bold
        '/(.*)\s+((?:^>.*+\n)+)/m' => 'self::blockquote', // blockquotes
    ];

    private static function blockquote($regs)
    {
        return '<details><summary>' . $regs[1] . '</summary><blockquote style="font-style:italic;">'
         . preg_replace('/^>/m', '', $regs[2]) . '</blockquote></details>';
    }

    /**
     * Render some Markdown into HTML.
     */
    public static function render($text)
    {
        $text = "\n" . $text . "\n";
        foreach (self::$rules as $regex => $replacement) {
            if (is_callable($replacement)) {
                $text = preg_replace_callback($regex, $replacement, $text);
            } else {
                $text = preg_replace($regex, $replacement, $text);
            }
        }
        return trim($text);
    }
}
