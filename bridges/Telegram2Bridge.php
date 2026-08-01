<?php

declare(strict_types=1);

class Telegram2Bridge extends BridgeAbstract
{
    const NAME = 'Telegram2';
    const URI = 'https://t.me';
    const DESCRIPTION = 'Returns the recent publications from a public Telegram channel. Supports embedded media contens and socks proxy, hides ads and unsupported content.';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [[
        'username' => [
            'name' => 'Channel name',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'durov',
        ],
        'limit' => [
            'name' => 'Max posts',
            'type' => 'number',
            'required' => false,
            'defaultValue' => 10,
        ],
        'use_proxy' => [
            'name' => 'Use proxy',
            'type' => 'checkbox',
            'defaultValue' => 'checked',
            'title' => 'Route requests through the SOCKS proxy configured on the server',
        ],
        'embed_media' => [
            'name' => 'Embed media',
            'type' => 'list',
            'values' => [
                'Auto (follow proxy)' => 'auto',
                'Always embed' => 'on',
                'Never embed' => 'off',
            ],
            'defaultValue' => 'auto',
            'title' => 'Download media and embed it as data URIs, so clients need no access to Telegram CDN',
        ],
        'skip_unsupported' => [
            'name' => 'Skip unsupported content',
            'type' => 'checkbox',
            'defaultValue' => 'checked',
            'title' => 'Hide unsupported content, skip posts that contain only unsupported media',
        ],
        'hide_hashtags' => [
            'name' => 'Hide hashtags',
            'type' => 'checkbox',
            'defaultValue' => 'checked',
            'title' => 'Remove hashtags from post text and assign them as feed item categories',
        ],
        'include_keywords' => [
            'name' => 'Include keywords',
            'type' => 'text',
            'required' => false,
            'title' => 'Show ONLY posts matching keywords. '
                . 'Syntax is the same as Exclude keywords: comma-separated rules, '
                . '"+" joins words with AND, matching is substring-based and case-insensitive. '
                . 'A post is kept only if it matches at least one rule. '
                . 'When both Include and Exclude are set, '
                . 'a post must first match Include, then survive Exclude.',
        ],
        'exclude_keywords' => [
            'name' => 'Exclude keywords',
            'type' => 'text',
            'required' => false,
            'title' => 'Hide posts matching keywords. '
                . 'Rules are comma-separated, case-insensitive, and matched as substrings '
                . 'against both title and body. '
                . 'A rule without "+" hides any post containing it '
                . '(e.g. "casino" also matches "casinos"). '
                . 'Join words with "+" to require ALL of them '
                . '(e.g. "casino+bonus" hides a post only if both words are present). '
                . 'Multiple rules act as OR: a post is hidden if it matches ANY rule. '
                . 'Example: "casino, bonus+promo, ads" hides posts with "casino", '
                . 'or with both "bonus" and "promo", or with "ads".',
        ],
    ]];

    const CONFIGURATION = [
        'proxy_url' => [
            'required' => false,
            'defaultValue' => '',
        ],
        'embed_max_size' => [
            'required' => false,
            'defaultValue' => '10m',
        ],
    ];

    private const BG_IMG_RE = "/background-image:url\('(.*)'\)/";
    private const TG_HOSTS = '(?:[\w-]+\.)*(?:telegram\.org|t\.me|telesco\.pe)';

    private const MAX_PAGES = 100;
    private const PROXY_TIMEOUT = 30;
    private const PROXY_RETRIES = 3;
    private const PAGE_DELAY_US = 500000;
    private const RETRY_BACKOFF_US = 1000000;

    private const MAX_TITLE_LENGTH = 60;
    private const MIN_TITLE_SPACE_POS = 30;
    private const MIN_REMAINDER_LENGTH = 12;
    private const SHORT_POST_MAX_LENGTH = 100;

    private const REASON_TOO_BIG = 'too_big';
    private const REASON_DEFAULT = 'default';

    private const ALLOWED_TAGS = '<div><a><p><br><hr><b><i><u><s><strong><em><code>'
        . '<pre><blockquote><span><img><video><source><ul><ol><li>';

    private const CSS = [
        'unsup_wrap'  => 'background:#17212b;border-radius:12px;padding:28px 16px;text-align:center',
        'unsup_label' => 'color:#708499;font-size:14px;margin-bottom:16px',
        'unsup_btn'   => 'display:inline-block;background:#2b5278;color:#6ab2f2;text-decoration:none;'
                       . 'text-transform:uppercase;font-weight:bold;font-size:13px;'
                       . 'letter-spacing:0.03em;padding:10px 24px;border-radius:8px',
        'video'       => 'max-width:100%',
        'wrapper'     => 'font-size:14px;line-height:1.6;word-wrap:break-word',
        'quote'       => 'border-left:4px solid #4a76a8;padding-left:12px;margin:8px 0',
        'poll'        => 'background:#f9f9f9;padding:15px;margin:10px 0;border-left:4px solid #4a76a8',
        'poll_t'      => 'margin:0 0 10px 0;font-weight:bold',
        'poll_o'      => 'margin:8px 0',
        'poll_f'      => 'margin:10px 0 0 0;color:#888;font-size:0.85em',
    ];

    private $feedName = '';
    private $feedIcon = '';
    private $itemTitle = '';
    private $itemAuthor = '';
    private $hashtags = [];
    private array $mediaCache = [];

    public function collectData()
    {
        $url = 'https://t.me/s/' . $this->normalizeUsername();
        $limit = max(1, (int)($this->getInput('limit') ?: 10));
        $pages = 0;
        $done = false;
        $seen = [];

        while ($pages < self::MAX_PAGES && !$done) {
            $pages++;

            if ($pages > 1) {
                usleep(self::PAGE_DELAY_US);
            }

            $dom = $this->fetchPage($url);
            if ($dom === null) {
                break;
            }

            if (!$this->feedName) {
                $el = $dom->find('div.tgme_channel_info_header_title span', 0);
                $this->feedName = htmlspecialchars_decode($el->plaintext ?? '', ENT_QUOTES);
            }

            if (!$this->feedIcon && !$this->getInput('use_proxy')) {
                $this->feedIcon = $this->extractChannelIcon($dom);
            }

            $messages = $dom->find('div.tgme_widget_message_wrap.js-widget_message_wrap');
            if (!$this->feedName && !$messages) {
                throwClientException('Unable to find channel. The channel is non-existing or non-public.');
            }

            foreach (array_reverse($messages) as $message) {
                if (count($this->items) >= $limit) {
                    $done = true;
                    break;
                }

                $item = $this->parseMessage($message);
                $notSupported = $this->detectNotSupported($message);
                $hasContent = trim(strip_tags($item['content'])) !== ''
                    || trim($item['title']) !== '';

                if (!$hasContent && $notSupported === null) {
                    continue;
                }

                if (!$hasContent && $this->getInput('skip_unsupported')) {
                    continue;
                }

                if ($notSupported !== null && $hasContent && $this->isShortPost($item)) {
                    continue;
                }

                if ($notSupported !== null && !$this->getInput('skip_unsupported')) {
                    $this->applyNotSupportedStub($item, $message, $notSupported, $hasContent);
                }

                if ($this->isBlocked($item, $message)) {
                    continue;
                }

                $this->items[] = $item;
            }

            if ($done) {
                break;
            }

            $more = $dom->find('> div.tgme_widget_message_centered.js-messages_more_wrap a', 0);
            if ($more && strpos($more->href, 'before') !== false) {
                $next = 'https://t.me' . $more->href;
                if (isset($seen[$next])) {
                    break;
                }
                $seen[$next] = true;
                $url = $next;
            } else {
                break;
            }
        }
    }

    public function getURI()
    {
        if ($this->getInput('username')) {
            return self::URI . '/s/' . $this->normalizeUsername();
        }

        return parent::getURI();
    }

    public function getName()
    {
        return $this->feedName ?: parent::getName();
    }

    public function getIcon()
    {
        return $this->feedIcon ?: parent::getIcon();
    }

    public function detectParameters($url)
    {
        $re = '/^https?:\/\/(?:(?:t|telegram)\.me\/(?:s\/)?([\w]+)|([\w]+)\.t\.me\/?)$/';

        if (preg_match($re, $url, $m)) {
            $username = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
            if ($username !== '') {
                return ['username' => $username];
            }
        }

        return null;
    }

    private function fetchPage(string $url)
    {
        for ($i = 0; $i < self::PROXY_RETRIES; $i++) {
            try {
                return getSimpleHTMLDOM($url, [], $this->getProxyOpts());
            } catch (\Exception $e) {
                $this->logger->warning(sprintf(
                    'Page fetch failed (attempt %d/%d): %s — %s',
                    $i + 1,
                    self::PROXY_RETRIES,
                    $url,
                    $e->getMessage()
                ));

                if ($i < self::PROXY_RETRIES - 1) {
                    usleep(($i + 1) * self::RETRY_BACKOFF_US);
                }
            }
        }

        return null;
    }

    private function getProxyOpts(): array
    {
        $opts = [
            CURLOPT_CONNECTTIMEOUT => self::PROXY_TIMEOUT,
            CURLOPT_TIMEOUT        => self::PROXY_TIMEOUT * 4,
        ];

        if ($this->getInput('use_proxy')) {
            $proxy = trim($this->getOption('proxy_url') ?? '');
            if ($proxy !== '') {
                $opts[CURLOPT_PROXY] = $proxy;
            }
        }

        return $opts;
    }

    private function parseMessage($message): array
    {
        $this->itemTitle = '';
        $this->itemAuthor = '';
        $this->hashtags = [];

        $item = [];

        $el = $message->find('a.tgme_widget_message_date', 0);
        if ($el) {
            $item['uri'] = $el->href;
        }

        $item['content'] = $this->processContent($message);
        $item['title'] = $this->itemTitle;

        if ($this->itemAuthor !== '' && $this->itemAuthor !== $this->feedName) {
            $item['author'] = $this->itemAuthor;
        }

        $el = $message->find('span.tgme_widget_message_meta time', 0);
        if ($el && $el->datetime) {
            $item['timestamp'] = $el->datetime;
        }

        $item['content'] = $this->removeViewInTelegram($item['content']);
        $item['content'] = $this->normalizeText($item['content']);

        if (!$this->getInput('hide_hashtags') && !empty($this->hashtags)) {
            $item['categories'] = $this->hashtags;
        }

        $item['content'] = $this->embedMediaInHtml($item['content']);
        $item['content'] = $this->sanitizeContent($item['content']);

        return $item;
    }

    private function processContent($messageDiv): string
    {
        foreach ($messageDiv->find('div.media_not_supported_cont') as $fake) {
            $fake->outertext = '';
        }

        $html = '';

        $fwd = $messageDiv->find('div.tgme_widget_message_forwarded_from', 0);
        if ($fwd) {
            $this->itemAuthor = $this->extractForwardedAuthor($fwd);
        }

        $reply = $messageDiv->find('a.tgme_widget_message_reply', 0);
        if ($reply) {
            $html .= $this->processReply($reply);
        }

        $inner = $messageDiv->innertext;

        $textPieces = [];

        $textDiv = $messageDiv->find('div.tgme_widget_message_text.js-message_text', 0);
        if ($textDiv) {
            $pos = strpos($inner, $textDiv->outertext);
            $textPieces[] = [$pos !== false ? $pos : PHP_INT_MAX, 'processText', $textDiv];
        }

        $mediaPieces = [];

        $mediaMarkers = [
            'tgme_widget_message_sticker_wrap'  => 'processSticker',
            'tgme_widget_message_poll'          => 'processPoll',
            'tgme_widget_message_photo_wrap'    => 'processPhoto',
            'tgme_widget_message_document'      => 'processAttachment',
            'tgme_widget_message_link_preview'  => 'processLinkPreview',
            'tgme_widget_message_location_wrap' => 'processLocation',
        ];

        foreach ($mediaMarkers as $marker => $method) {
            $el = $messageDiv->find('div.' . $marker, 0)
                ?: $messageDiv->find('a.' . $marker, 0);
            if ($el) {
                $pos = strpos($inner, $el->outertext);
                $mediaPieces[] = [$pos !== false ? $pos : PHP_INT_MAX, $method, $messageDiv];
            }
        }

        $videoNotSupported = $messageDiv->find('a.tgme_widget_message_video_player.not_supported', 0)
            ?: $messageDiv->find('div.tgme_widget_message_video_player.not_supported', 0);

        if (!$videoNotSupported && $messageDiv->find('video', 0)) {
            $pos = strpos($inner, '<video');
            if ($pos !== false) {
                $mediaPieces[] = [$pos, 'processVideo', $messageDiv];
            }
        }

        usort($textPieces, fn($a, $b) => $a[0] <=> $b[0]);
        usort($mediaPieces, fn($a, $b) => $a[0] <=> $b[0]);

        foreach (array_merge($textPieces, $mediaPieces) as $piece) {
            $partHtml = $this->{$piece[1]}($piece[2]);

            if ($partHtml === '') {
                continue;
            }

            if ($html !== '') {
                $html .= '<br /><br />';
            }
            $html .= $partHtml;
        }

        return $html;
    }

    private function processText($textDiv): string
    {
        $nested = $textDiv->find('div.tgme_widget_message_text.js-message_text', 0);
        if ($nested) {
            $textDiv = $nested;
        }

        $inner = $textDiv->innertext;

        $this->hashtags = $this->extractHashtags($inner);

        $plain = html_entity_decode(
            preg_replace('/\s+/u', ' ', strip_tags(
                preg_replace('/<br\s*\/?>/i', ' ', $inner)
            )),
            ENT_QUOTES | ENT_HTML5
        );

        if (mb_strlen($plain, 'UTF-8') <= self::MAX_TITLE_LENGTH) {
            $this->itemTitle = $plain;
            return '';
        }

        $split = $this->splitTitleAndContent($inner);
        $this->itemTitle = $split['title'];

        if ($split['html'] === '') {
            return '';
        }

        $dir = $textDiv->getAttribute('dir');
        $attr = $dir ? ' dir="' . $dir . '"' : '';

        return '<div class="tgme_widget_message_text js-message_text"' . $attr . '>'
            . $split['html'] . '</div>';
    }

    private function processReply($reply): string
    {
        $author = htmlspecialchars(
            $this->getPlaintext($reply, 'span.tgme_widget_message_author_name'),
            ENT_QUOTES
        );
        $text = '';

        $el = $reply->find('div.tgme_widget_message_metatext', 0);
        if ($el) {
            $text = $el->innertext;
        }

        $el = $reply->find('div.tgme_widget_message_text', 0);
        if ($el) {
            $text = $el->innertext;
        }

        $href = htmlspecialchars($reply->href, ENT_QUOTES);

        return '<blockquote>' . $author . '<br />' . $text
            . '<a href="' . $href . '">' . $href . '</a></blockquote><hr />';
    }

    private function processPhoto($messageDiv): string
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a photo';
        }

        $out = '';
        foreach ($messageDiv->find('a.tgme_widget_message_photo_wrap') as $wrap) {
            if (preg_match(self::BG_IMG_RE, $wrap->style, $m)) {
                $out .= '<a href="' . $wrap->href . '"><img src="' . $m[1] . '" /></a>';
            }
        }

        return $out;
    }

    private function processVideo($messageDiv): string
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a video';
        }

        $poster = '';
        $thumbs = [
            'i.tgme_widget_message_video_thumb',
            'i.link_preview_video_thumb',
            'i.tgme_widget_message_roundvideo_thumb',
        ];

        foreach ($thumbs as $sel) {
            $el = $messageDiv->find($sel, 0);
            if ($el && preg_match(self::BG_IMG_RE, $el->style, $m)) {
                $poster = $m[1];
                break;
            }
        }

        $player = $messageDiv->find('a.tgme_widget_message_video_player', 0)
            ?: $messageDiv->find('div.tgme_widget_message_video_player', 0);

        $postHref = '';
        if ($player && $player->href) {
            $postHref = $player->href;
            if (strpos($postHref, 'http') !== 0) {
                $postHref = self::URI . '/' . ltrim($postHref, '/');
            }
        }

        $videoEl = $messageDiv->find('video', 0);
        $src = $videoEl ? ($videoEl->src ?? '') : '';

        if ($poster === '' && $src === '' && $postHref === '') {
            return '';
        }

        $href = $postHref ?: '#';

        $channel = $this->feedName !== ''
            ? htmlspecialchars($this->feedName, ENT_QUOTES)
            : ('@' . $this->normalizeUsername());

        $duration = $this->getPlaintext($messageDiv, 'time.tgme_widget_message_video_duration');
        if ($duration === '') {
            $duration = $this->getPlaintext($messageDiv, 'span.tgme_widget_message_video_duration');
        }
        if ($duration === '') {
            $duration = $this->getPlaintext($messageDiv, 'time.message_video_duration');
        }
        $duration = htmlspecialchars($duration, ENT_QUOTES);

        $resolution = '';
        if ($player && $player->style) {
            if (preg_match('/width:\s*(\d+)px/i', $player->style, $mw)
                && preg_match('/height:\s*(\d+)px/i', $player->style, $mh)) {
                $resolution = $mw[1] . '?' . $mh[1];
            }
        }

        $label = 'Video: ' . $channel;
        if ($duration !== '') {
            $label .= ' (' . $duration . ')';
        }
        if ($resolution !== '') {
            $label .= ' (' . $resolution . ')';
        }

        $html = '';

        if ($poster !== '') {
            $html .= '<a href="' . $href . '">'
                . '<img src="' . $poster . '" style="' . self::CSS['video'] . '" />'
                . '</a><br />';
        }

        $html .= '<a href="' . $href . '">' . $label . '</a>';

        return $html;
    }

    private function processSticker($messageDiv): string
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a sticker';
        }

        $div = $messageDiv->find('div.tgme_widget_message_sticker_wrap', 0);
        if (!$div) {
            return '';
        }

        $pic = $div->find('picture', 0);
        if ($pic) {
            $innerDiv = $pic->find('div', 0);
            if ($innerDiv) {
                $innerDiv->style = '';
            }
            $pic->style = '';
            return (string)$div;
        }

        $el = $div->find('i', 0);
        if ($el && preg_match(self::BG_IMG_RE, $el->style, $m)) {
            return '<img src="' . $m[1] . '" />';
        }

        return '';
    }

    private function processPoll($messageDiv): string
    {
        $poll = $messageDiv->find('div.tgme_widget_message_poll', 0);
        if (!$poll) {
            return '';
        }

        $title = $this->getPlaintext($poll, 'div.tgme_widget_message_poll_question');
        $type = $this->getPlaintext($poll, 'div.tgme_widget_message_poll_type');

        if (!$this->itemTitle) {
            $this->itemTitle = $title;
        }

        $html = '<div style="' . self::CSS['poll'] . '">';
        $html .= '<p style="' . self::CSS['poll_t'] . '">'
            . htmlspecialchars($title, ENT_QUOTES) . '</p>';

        foreach ($poll->find('div.tgme_widget_message_poll_option') as $opt) {
            $percent = $this->getPlaintext($opt, 'div.tgme_widget_message_poll_option_percent');
            $text = $this->getPlaintext($opt, 'div.tgme_widget_message_poll_option_text');

            $pct = max(0, min(100, (int)str_replace('%', '', $percent)));
            $filled = (int)round($pct / 5);
            $bar = '[' . str_repeat('#', $filled) . str_repeat('.', 20 - $filled) . ']';

            $html .= '<div style="' . self::CSS['poll_o'] . '">';
            $html .= '<b>' . $pct . '%</b> ' . htmlspecialchars($text, ENT_QUOTES) . '<br />';
            $html .= '<code>' . $bar . '</code>';
            $html .= '</div>';
        }

        $footer = [];

        $voters = htmlspecialchars(
            $this->getPlaintext($messageDiv, 'span.tgme_widget_message_voters'),
            ENT_QUOTES
        );
        if ($voters !== '') {
            $footer[] = $voters . ' voters';
        }

        if (stripos($type, 'anonymous') !== false) {
            $footer[] = 'Anonymous';
        }
        if (stripos($type, 'quiz') !== false) {
            $footer[] = 'Quiz';
        }
        if (stripos($type, 'multiple') !== false) {
            $footer[] = 'Multiple choice';
        }

        if (!empty($footer)) {
            $html .= '<p style="' . self::CSS['poll_f'] . '">'
                . implode(' &#183; ', $footer) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    private function processLinkPreview($messageDiv): string
    {
        $preview = $messageDiv->find('a.tgme_widget_message_link_preview', 0);
        if (!$preview || trim($preview->innertext) === '') {
            return '';
        }

        $img = '';
        $el = $preview->find('i', 0);
        if ($el && preg_match(self::BG_IMG_RE, $el->style, $m)) {
            $img = '<img src="' . $m[1] . '" />';
        }

        $title = htmlspecialchars($this->getPlaintext($preview, 'div.link_preview_title'), ENT_QUOTES);
        $site = htmlspecialchars($this->getPlaintext($preview, 'div.link_preview_site_name'), ENT_QUOTES);
        $desc = htmlspecialchars($this->getPlaintext($preview, 'div.link_preview_description'), ENT_QUOTES);

        return '<blockquote><a href="' . $preview->href . '">' . $img . '</a><br />'
            . '<a href="' . $preview->href . '">' . $title . ' - ' . $site . '</a><br />'
            . $desc . '</blockquote>';
    }

    private function processAttachment($messageDiv): string
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted an attachment';
        }

        $out = 'File attachments:<br />';
        foreach ($messageDiv->find('div.tgme_widget_message_document') as $doc) {
            $docTitle = htmlspecialchars($this->getPlaintext($doc, 'div.tgme_widget_message_document_title'), ENT_QUOTES);
            $docExtra = htmlspecialchars($this->getPlaintext($doc, 'div.tgme_widget_message_document_extra'), ENT_QUOTES);
            $out .= $docTitle . ' - ' . $docExtra . '<br />';
        }

        return $out;
    }

    private function processLocation($messageDiv): string
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a location';
        }

        $el = $messageDiv->find('div.tgme_widget_message_location', 0);
        $link = $messageDiv->find('a.tgme_widget_message_location_wrap', 0);

        if (!$el || !$link) {
            return '';
        }

        preg_match(self::BG_IMG_RE, $el->style, $m);

        return '<a href="' . $link->href . '"><img src="' . ($m[1] ?? '') . '" /></a>';
    }

    private function splitTitleAndContent(string $html): array
    {
        $html = preg_replace('/^(?:\s*<br\s*\/?>)+\s*/i', '', $html);

        if (preg_match('/<br\s*\/?>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $firstLineHtml = substr($html, 0, $m[0][1]);
            $firstLinePlain = html_entity_decode(
                preg_replace('/\s+/u', ' ', strip_tags($firstLineHtml)),
                ENT_QUOTES | ENT_HTML5
            );

            if ($firstLinePlain !== '' && mb_strlen($firstLinePlain, 'UTF-8') <= self::MAX_TITLE_LENGTH) {
                $restHtml = substr($html, $m[0][1] + strlen($m[0][0]));
                $restHtml = preg_replace('/^(?:\s*<br\s*\/?>)+\s*/i', '', $restHtml);

                return ['title' => $firstLinePlain, 'html' => trim($restHtml)];
            }
        }

        $paragraphs = preg_split('/(?:\s*<br\s*\/?>\s*){2,}/i', $html);
        $firstPlain = html_entity_decode(
            preg_replace('/\s+/u', ' ', strip_tags($paragraphs[0])),
            ENT_QUOTES | ENT_HTML5
        );

        if (mb_strlen($firstPlain, 'UTF-8') <= self::MAX_TITLE_LENGTH) {
            $restHtml = implode('<br /><br />', array_slice($paragraphs, 1));

            return ['title' => $firstPlain, 'html' => trim($restHtml)];
        }

        $prefix = $this->mbTruncateAtWord($firstPlain, self::MAX_TITLE_LENGTH);
        $remainder = trim(mb_substr($firstPlain, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8'));

        if (mb_strlen($remainder, 'UTF-8') < self::MIN_REMAINDER_LENGTH) {
            $sp = mb_strrpos($prefix, ' ', 0, 'UTF-8');
            if ($sp !== false && $sp > self::MIN_TITLE_SPACE_POS) {
                $prefix = rtrim(mb_substr($prefix, 0, $sp, 'UTF-8'));
            }
        }

        $firstHtml = $this->removeTextPrefix($paragraphs[0], $prefix);
        $restHtml = implode('<br /><br />', array_slice($paragraphs, 1));

        $contentHtml = $firstHtml;
        if ($restHtml !== '') {
            $contentHtml .= '<br /><br />' . $restHtml;
        }

        return ['title' => $prefix . '...', 'html' => trim($contentHtml)];
    }

    private function mbTruncateAtWord(string $text, int $length): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length, 'UTF-8');
        $sp = mb_strrpos($cut, ' ', 0, 'UTF-8');

        if ($sp !== false && $sp > self::MIN_TITLE_SPACE_POS) {
            $cut = mb_substr($cut, 0, $sp, 'UTF-8');
        }

        return rtrim($cut);
    }

    private function removeTextPrefix(string $html, string $prefix): string
    {
        $limit = mb_strlen($prefix, 'UTF-8');
        if ($limit <= 0) {
            return $html;
        }

        $void = ['br', 'img', 'hr', 'input', 'meta', 'link', 'source'];
        $tokens = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        $consumed = 0;
        $stack = [];
        $out = '';
        $cut = false;

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            if ($cut) {
                $out .= $token;
                continue;
            }

            if ($token[0] === '<') {
                if (preg_match('/^<\s*\/\s*([a-zA-Z0-9]+)/u', $token, $m)) {
                    $tag = strtolower($m[1]);
                    for ($i = count($stack) - 1; $i >= 0; $i--) {
                        if ($stack[$i]['tag'] === $tag) {
                            array_splice($stack, $i, 1);
                            break;
                        }
                    }
                } elseif (preg_match('/^<\s*([a-zA-Z0-9]+)/u', $token, $m)) {
                    $tag = strtolower($m[1]);
                    $selfClosing = in_array($tag, $void, true)
                        || substr(rtrim($token, '>'), -1) === '/';
                    if (!$selfClosing) {
                        $stack[] = ['tag' => $tag, 'html' => $token];
                    }
                }
                continue;
            }

            preg_match_all('/&[a-zA-Z]+;|&#\d+;|&#x[0-9a-fA-F]+;|./us', $token, $m);
            $units = $m[0];
            $nodeLen = count($units);

            if ($consumed + $nodeLen <= $limit) {
                $consumed += $nodeLen;
                continue;
            }

            $skip = $limit - $consumed;
            $cut = true;

            foreach ($stack as $open) {
                $out .= $open['html'];
            }
            $out .= implode('', array_slice($units, $skip));
        }

        if (!$cut) {
            return '';
        }

        $out = preg_replace('/^(?:\s*<br\s*\/?>)+\s*/i', '', $out);

        return '... ' . ltrim($out);
    }

    private function extractHashtags(string &$html): array
    {
        $tags = [];

        if (preg_match_all(
            '/<a\s[^>]*href="\?q=%23[^"]*"[^>]*>(.*?)<\/a>/is',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $m) {
                $text = trim(strip_tags($m[1]));
                if ($text !== '' && $text[0] === '#') {
                    $tags[] = mb_substr($text, 1, null, 'UTF-8');
                }
            }
        }

        $html = preg_replace_callback(
            '/<a\s[^>]*href="\?q=%23[^"]*"[^>]*>(.*?)<\/a>/is',
            function (array $m): string {
                $text = trim(strip_tags($m[1]));
                if ($text !== '' && $text[0] === '#') {
                    return '';
                }
                return $m[0];
            },
            $html
        );

        $html = preg_replace('/<b>\s*<\/b>/i', '', $html);
        $html = preg_replace('/ {2,}/', ' ', $html);
        $html = preg_replace('/^(?:\s*<br\s*\/?>)+\s*/i', '', $html);
        $html = preg_replace('/\s*(?:<br\s*\/?>)+\s*$/i', '', $html);

        return array_values(array_unique($tags));
    }

    private function detectNotSupported($message): ?array
    {
        $videoPlayer = $message->find('a.tgme_widget_message_video_player.not_supported', 0)
            ?: $message->find('div.tgme_widget_message_video_player.not_supported', 0);

        if ($videoPlayer) {
            return ['type' => 'video', 'element' => $videoPlayer];
        }

        if ($message->find('div.media_supported_cont', 0)) {
            return null;
        }

        if ($message->find('video', 0)) {
            return null;
        }

        if ($message->find('a.tgme_widget_message_photo_wrap', 0)) {
            return null;
        }

        $notSupportedWrap = $message->find('div.message_media_not_supported_wrap', 0);
        if ($notSupportedWrap) {
            return ['type' => 'generic', 'element' => $notSupportedWrap];
        }

        return null;
    }

    private function applyNotSupportedStub(
        array &$item,
        $message,
        array $info,
        bool $hasContent
    ): void {
        $stubLabel = '';
        $title = '';

        switch ($info['type']) {
            case 'video':
                $reason = $this->getUnsupportedReason($message);
                $stubLabel = $reason === self::REASON_TOO_BIG
                    ? 'Media is too big'
                    : 'Unsupported media';
                $title = 'Unsupported media';
                break;

            case 'generic':
            default:
                $stubLabel = 'Please open Telegram to view this post';
                $title = 'Unsupported content';
                break;
        }

        if (!$hasContent) {
            $item['title'] = $title;
            $item['content'] = $this->renderUnsupported($item['uri'] ?? '#', $stubLabel);
        } else {
            $stub = $this->renderUnsupported($item['uri'] ?? '#', $stubLabel);
            if (preg_match('/(<br\s*\/?>\s*){2,}\s*<\/div>\s*$/i', $item['content'])) {
                $item['content'] = preg_replace('/\s*<\/div>\s*$/', $stub . '</div>', $item['content']);
            } elseif (preg_match('/<br\s*\/?>\s*<\/div>\s*$/i', $item['content'])) {
                $item['content'] = preg_replace('/\s*<\/div>\s*$/', '<br />' . $stub . '</div>', $item['content']);
            } else {
                $item['content'] = preg_replace('/\s*<\/div>\s*$/', '<br /><br />' . $stub . '</div>', $item['content']);
            }
        }
    }

    private function getUnsupportedReason($message): string
    {
        $label = $message->find('div.message_media_not_supported_label', 0);
        $text = $label ? trim($label->plaintext) : '';

        if (stripos($text, 'too big') !== false || stripos($text, 'too large') !== false) {
            return self::REASON_TOO_BIG;
        }

        return self::REASON_DEFAULT;
    }

    private function renderUnsupported(
        string $uri,
        string $label = 'Please open Telegram to view this post'
    ): string {
        return '<blockquote style="' . self::CSS['unsup_wrap'] . '">'
            . '<div style="' . self::CSS['unsup_label'] . '">' . $label . '</div>'
            . '<a href="' . $uri . '" style="' . self::CSS['unsup_btn'] . '">'
            . '<b>View in Telegram</b></a></blockquote>';
    }

    private function removeViewInTelegram(string $html): string
    {
        $html = preg_replace('/<a[^>]*>\s*<\/a>/', '', $html);
        $html = preg_replace('/(<br\s*\/?>){3,}/i', '<br /><br />', $html);

        return trim($html);
    }

    private function normalizeText(string $html): string
    {
        $html = preg_replace('/<tg-emoji[^>]*>(.*?)<\/tg-emoji>/is', '$1', $html);
        $html = preg_replace('/<i\s[^>]*class="emoji"[^>]*>(.*?)<\/i>/is', '$1', $html);

        $html = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $html);
        $html = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $html);
        $html = preg_replace(
            '/[\x{00A0}\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u',
            ' ',
            $html
        );
        $html = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}]/u', '', $html);

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($html, \Normalizer::FORM_KD);
            if ($normalized !== false) {
                $html = $normalized;
            }
        }

        $html = preg_replace_callback(
            '/href\s*=\s*["\'](https?:\/\/[^"\']+)["\']/i',
            function (array $m): string {
                $url = preg_replace(
                    '/[?&](utm_\w+|fbclid|gclid|yclid|dclid|tg_rhash)=[^&]*/',
                    '',
                    $m[1]
                );
                $url = preg_replace('/\?$/', '', $url);

                return 'href="' . $url . '"';
            },
            $html
        );

        return preg_replace('/ {2,}/', ' ', $html);
    }

    private function sanitizeContent(string $html): string
    {
        $html = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*\'[^\']*\'/i', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html);

        $html = preg_replace_callback(
            '/(href|src)\s*=\s*["\']([^"\']*)["\']/i',
            function (array $m): string {
                $url = $m[2];

                if (preg_match('/^\s*(javascript|vbscript|data(?!:(?:image|video|audio)\/))/i', $url)) {
                    return $m[1] . '="#"';
                }

                if (strpos($url, '?') === 0 || strpos($url, '/') === 0) {
                    return $m[1] . '="' . self::URI . '/s/' . $this->normalizeUsername() . $url . '"';
                }

                return $m[1] . '="' . $url . '"';
            },
            $html
        );

        $html = preg_replace('/\s+(class|id|data-[\w-]+)\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\sexpandable(?=[\s>])/i', '', $html);

        $html = preg_replace_callback(
            '/\s+style\s*=\s*["\']([^"\']*)["\']/i',
            function (array $m): string {
                $val = $m[1];
                $val = preg_replace('/expression\s*\(/i', '', $val);
                $val = preg_replace('/javascript\s*:/i', '', $val);
                $val = preg_replace('/vbscript\s*:/i', '', $val);
                $val = preg_replace('/behavior\s*:/i', '', $val);
                $val = preg_replace('/@import\b/i', '', $val);
                $val = preg_replace('/url\s*\(\s*["\']?\s*javascript:/i', 'url(', $val);
                $val = trim($val);
                if ($val === '') {
                    return '';
                }
                return ' style="' . htmlspecialchars($val, ENT_QUOTES) . '"';
            },
            $html
        );

        $html = preg_replace('/<\/?tg-spoiler>/i', '', $html);

        $html = strip_tags($html, self::ALLOWED_TAGS);

        $html = preg_replace(
            '/<blockquote(\s[^>]*)?>/i',
            '<blockquote$1 style="' . self::CSS['quote'] . '">',
            $html
        );

        $html = preg_replace('/<a[^>]*>\s*<\/a>/', '', $html);
        $html = preg_replace('/(<br\s*\/?>){3,}/i', '<br /><br />', $html);

        return '<div style="' . self::CSS['wrapper'] . '">' . trim($html) . '</div>';
    }

    private function shouldEmbedMedia(): bool
    {
        $mode = $this->getInput('embed_media') ?? 'auto';

        if ($mode === 'on') {
            return true;
        }

        if ($mode === 'off') {
            return false;
        }

        return (bool)$this->getInput('use_proxy');
    }

    private function embedMediaInHtml(string $html): string
    {
        if (!$this->shouldEmbedMedia()) {
            return $html;
        }

        $re = '/(src|poster)\s*=\s*["\']'
            . '(https?:\/\/' . self::TG_HOSTS . '\/[^"\'\s>]+)'
            . '["\']/i';

        $result = preg_replace_callback($re, function (array $m): string {
            return $m[1] . '="' . $this->urlToDataUri($m[2]) . '"';
        }, $html);

        return $result ?? $html;
    }

    private function urlToDataUri(string $url): string
    {
        $data = $this->fetchMediaCached($url);
        if ($data === null) {
            return $url;
        }

        $maxSize = $this->parseSize($this->getOption('embed_max_size') ?: '10m');
        if ($maxSize > 0 && strlen($data['body']) > $maxSize) {
            return $url;
        }

        return 'data:' . $data['type'] . ';base64,' . base64_encode($data['body']);
    }

    private function fetchMediaCached(string $url): ?array
    {
        if (array_key_exists($url, $this->mediaCache)) {
            return $this->mediaCache[$url];
        }

        $opts = $this->getProxyOpts();

        for ($i = 0; $i < self::PROXY_RETRIES; $i++) {
            try {
                $response = getContents($url, [], $opts, true);

                $body = $response->getBody();
                $ct = $response->getHeaders()['content-type'][0] ?? 'application/octet-stream';
                $type = trim(explode(';', $ct)[0]);

                if ($body === '' || $body === null) {
                    $this->mediaCache[$url] = null;
                    return null;
                }

                $this->mediaCache[$url] = ['body' => $body, 'type' => $type];
                return $this->mediaCache[$url];
            } catch (\Exception $e) {
                $this->logger->warning(sprintf(
                    'Media fetch failed (attempt %d/%d): %s — %s',
                    $i + 1,
                    self::PROXY_RETRIES,
                    $url,
                    $e->getMessage()
                ));

                if ($i < self::PROXY_RETRIES - 1) {
                    usleep(($i + 1) * self::RETRY_BACKOFF_US);
                }
            }
        }

        $this->mediaCache[$url] = null;
        return null;
    }

    private function parseSize($value): int
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 0;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*([kmg])?b?$/i', $value, $m)) {
            $mult = ['' => 1, 'k' => 1024, 'm' => 1048576, 'g' => 1073741824];
            $unit = strtolower($m[2] ?? '');

            return (int)round((float)$m[1] * $mult[$unit]);
        }

        return (int)$value;
    }

    private function isBlocked(array $item, $message): bool
    {
        if ($this->isAd($message)) {
            return true;
        }

        $haystack = $this->buildSearchHaystack($item);

        $exclude = trim($this->getInput('exclude_keywords') ?? '');
        if ($exclude !== '' && $this->matchesKeywordRules($haystack, $exclude)) {
            return true;
        }

        $include = trim($this->getInput('include_keywords') ?? '');
        if ($include !== '' && !$this->matchesKeywordRules($haystack, $include)) {
            return true;
        }

        return false;
    }

    private function buildSearchHaystack(array $item): string
    {
        return mb_strtolower(
            trim(($item['title'] ?? '') . ' ' . strip_tags($item['content'] ?? '')),
            'UTF-8'
        );
    }

    private function matchesKeywordRules(string $haystack, string $rules): bool
    {
        if ($haystack === '' || $rules === '') {
            return false;
        }

        foreach (explode(',', $rules) as $rule) {
            $rule = trim($rule);
            if ($rule === '') {
                continue;
            }

            if (strpos($rule, '+') !== false) {
                $parts = array_filter(
                    array_map(
                        fn(string $p): string => mb_strtolower(trim($p), 'UTF-8'),
                        explode('+', $rule)
                    ),
                    fn(string $p): bool => $p !== ''
                );

                if (empty($parts)) {
                    continue;
                }

                $all = true;
                foreach ($parts as $part) {
                    if (strpos($haystack, $part) === false) {
                        $all = false;
                        break;
                    }
                }

                if ($all) {
                    return true;
                }
            } elseif (strpos($haystack, mb_strtolower($rule, 'UTF-8')) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isAd($message): bool
    {
        foreach ($message->find('[class]') as $el) {
            if (stripos($el->class ?? '', 'sponsored') !== false) {
                return true;
            }
        }

        return false;
    }

    private function isShortPost(array $item): bool
    {
        $plain = trim(($item['title'] ?? '') . ' ' . strip_tags($item['content'] ?? ''));
        return mb_strlen($plain, 'UTF-8') <= self::SHORT_POST_MAX_LENGTH;
    }

    private function normalizeUsername(): string
    {
        return ltrim(trim($this->getInput('username')), '@');
    }

    private function extractChannelIcon($dom): string
    {
        foreach ($dom->find('meta') as $meta) {
            if ($meta->getAttribute('property') === 'og:image') {
                $content = trim($meta->content ?? '');
                if ($content !== '') {
                    return $content;
                }
            }
        }

        $el = $dom->find('i.tgme_page_photo_image img', 0);
        if ($el) {
            $src = trim($el->src ?? '');
            if ($src !== '') {
                return $src;
            }
        }

        return '';
    }

    private function extractForwardedAuthor($fwd): string
    {
        $author = $fwd->find('span.tgme_widget_message_forwarded_from_author', 0);
        if ($author) {
            $text = trim($author->plaintext);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function getPlaintext($element, string $selector): string
    {
        $el = $element->find($selector, 0);
        return $el ? trim($el->plaintext) : '';
    }
}