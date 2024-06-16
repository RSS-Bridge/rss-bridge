<?php

class Mailman2Bridge extends BridgeAbstract
{
    const NAME = 'Mailman2Bridge';
    const URI = 'https://list.org';
    const MAINTAINER = 'imagoiq';
    const CACHE_TIMEOUT = 60 * 30; // 30m
    const DESCRIPTION = 'Fetch latest messages from Mailman 2 archive (Pipermail)';

    const PARAMETERS = [
        'Mailman 2' => [
            'url' => [
                'name' => 'Enter web archive URL',
                'title' => <<<"EOL"
                            Specify the URL from the archive page where all the archive are listed by month.
                            EOL
                , 'type' => 'text',
                'exampleValue' => 'https://mailman.nginx.org/pipermail/nginx-announce/',
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
        $url = $this->getInput('url');
        $limit = $this->getInput('limit');

        $html = defaultLinkTo(getSimpleHTMLDOMCached($url, 1800), $url);

        // Fetch archive urls from the frontpage
        $archives = [];
        foreach ($html->find('tr') as $key => $tr) {
            $archiveUrl = $tr->find('a[href$="date.html"]', 0);
            $downloadUrl = $tr->find('a[href$=".txt"], a[href$=".txt.gz"]', 0);
            $archives[$key] = [
                'bydate' => $archiveUrl ? $archiveUrl->getAttribute('href') : null,
                'download' => $downloadUrl ? $downloadUrl->getAttribute('href') : null
            ];
        }

        foreach ($archives as $archive) {
            if (!$archive['bydate']) {
                continue;
            }

            // Fetch urls to mails
            $parent = pathinfo($archive['bydate'], PATHINFO_DIRNAME) . '/';
            $html = defaultLinkTo(getSimpleHTMLDOMCached($archive['bydate'], 1800), $parent);
            $links = array_map(function ($val) {
                return $val->getAttribute('href');
            }, $html->find('ul', 1)->find('li a[href$=".html"]'));
            $mailUrls = array_reverse($links);

            // Parse mbox
            $data = getContents($archive['download']);
            if (str_ends_with($archive['download'], '.gz')) {
                $data = \gzdecode($data, (1024 ** 2) * 25); // 25M
                if ($data === false) {
                    throw new \Exception('Failed to gzdecode');
                }
            }
            $mboxParts = preg_split('/^From\s.+\d{2}:\d{2}:\d{2}\s\d{4}$/m', $data);
            // Drop the first element which is always an empty string
            array_shift($mboxParts);
            $mboxMails = array_reverse($mboxParts);
            foreach ($mboxMails as $index => $content) {
                // Match Urls with contents from txt files.
                // Urls cannot be reconstructed from the txt content.
                $mails[] = [
                    'url' => $mailUrls[$index],
                    'content' => $content
                ];
            }
            if (count($mails) > $limit) {
                break;
            }
        }

        $pluck = function ($header, $mail) {
            // Not necessary to escape the header here
            $pattern = sprintf('/(?<=%s:).*$/m', $header);
            if (preg_match($pattern, $mail, $m)) {
                return trim(\mb_decode_mimeheader($m[0]));
            }
            return null;
        };
        foreach (array_slice($mails, 0, $limit) as $mail) {
            $item = [];
            $item['uid'] = $pluck('Message-ID', $mail['content']);
            $item['uri'] = $mail['url'];
            $item['title'] = $pluck('Subject', $mail['content']);
            $item['author'] = preg_replace('/\sat\s/', '@', $pluck('From', $mail['content']));
            $item['timestamp'] = $pluck('Date', $mail['content']);
            $item['content'] = nl2br(self::render($mail['content']));
            $this->items[] = $item;
        }
    }

    /**
     * Parse mbox mail. Render some useful html.
     *
     * Based on https://gist.github.com/jbroadway/2836900
     */
    private static function render($text)
    {
        $rules = [
            '/[\s\S]*?^Message-ID:[\s\S]*?>\n\n/m' => '', // Metadata
            '/-+\s+next part\s+-+[\s\S]+?(?=^$|\Z)/m' => '', // next part
            '/(?<!href=[\'"])https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.
        [a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()!@:%_\+.,~#?&\/\/=]*)/' => '<a href="$0">$0</a>', // links
            '/(\w+) <(.*) at (.*)>/' => '<a href="mailto:$2@$3">$1</a>', // emails
            '/(\*|\*\*|__)(.*?)\1/' => '<strong>\2</strong>', // bold
            // blockquotes
            '/(.*)\s+((?:^>.*+\n)+)/m' => function ($regs) {
                return sprintf(
                    '<details><summary>%s</summary><blockquote style="font-style:italic;">%s</blockquote></details>',
                    $regs[1],
                    preg_replace('/^>/m', '', $regs[2])
                );
            },
        ];

        $text = "\n" . $text . "\n";
        foreach ($rules as $regex => $replacement) {
            if (is_callable($replacement)) {
                $text = preg_replace_callback($regex, $replacement, $text);
            } else {
                $text = preg_replace($regex, $replacement, $text);
            }
        }
        return trim($text);
    }
}
