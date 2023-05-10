<?php

class TelegramBridge extends BridgeAbstract
{
    const NAME = 'Telegram Bridge';
    const URI = 'https://t.me';
    const DESCRIPTION = 'Returns newest posts from a *public* Telegram channel';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [[
            'username' => [
                'name' => 'Username',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '@rssbridge',
            ]
        ]
    ];
    const TEST_DETECT_PARAMETERS = [
        'https://t.me/s/rssbridge' => ['username' => 'rssbridge'],
        'https://t.me/rssbridge' => ['username' => 'rssbridge'],
        'http://t.me/rssbridge' => ['username' => 'rssbridge'],
        'https://telegram.me/s/rssbridge' => ['username' => 'rssbridge'],
        'https://telegram.me/rssbridge' => ['username' => 'rssbridge'],
        'http://telegram.me/rssbridge' => ['username' => 'rssbridge'],
        'http://rssbridge.t.me/' => ['username' => 'rssbridge'],
        'https://rssbridge.t.me/' => ['username' => 'rssbridge'],
    ];

    const CACHE_TIMEOUT = 60 * 15; // 15 mins
    private $feedName = '';

    private $enclosures = [];
    private $itemTitle = '';

    private const BACKGROUND_IMAGE_REGEX = "/background-image:url\('(.*)'\)/";

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $channelTitle = $html->find('div.tgme_channel_info_header_title span', 0)->plaintext ?? '';
        $channelTitle = htmlspecialchars_decode($channelTitle, ENT_QUOTES);
        $this->feedName = $channelTitle . ' (@' . $this->normalizeUsername() . ')';
        $posts = $html->find('div.tgme_widget_message_wrap.js-widget_message_wrap');
        if (!$channelTitle && !$posts) {
            throw new \Exception('Unable to find channel. The channel is non-existing or non-public.');
        }
        foreach ($posts as $messageDiv) {
            $this->itemTitle = '';
            $this->enclosures = [];
            $item = [];

            $item['uri'] = $messageDiv->find('a.tgme_widget_message_date', 0)->href;
            $item['content'] = $this->processContent($messageDiv);
            $item['title'] = $this->itemTitle;
            $item['timestamp'] = $messageDiv->find('span.tgme_widget_message_meta', 0)->find('time', 0)->datetime;
            $item['enclosures'] = $this->enclosures;
            $author = trim($messageDiv->find('a.tgme_widget_message_owner_name', 0)->plaintext);
            $item['author'] = html_entity_decode($author, ENT_QUOTES);

            $this->items[] = $item;
        }
        $this->items = array_reverse($this->items);
    }

    private function processContent($messageDiv)
    {
        $message = '';

        if ($messageDiv->find('div.tgme_widget_message_forwarded_from', 0)) {
            $message = $messageDiv->find('div.tgme_widget_message_forwarded_from', 0)->innertext . '<br><br>';
        }

        if ($messageDiv->find('a.tgme_widget_message_reply', 0)) {
            $message .= $this->processReply($messageDiv);
        }

        if ($messageDiv->find('div.tgme_widget_message_sticker_wrap', 0)) {
            $message .= $this->processSticker($messageDiv);
        }

        if ($messageDiv->find('div.tgme_widget_message_poll', 0)) {
            $message .= $this->processPoll($messageDiv);
        }

        if ($messageDiv->find('video', 0)) {
            $message .= $this->processVideo($messageDiv);
        }

        if ($messageDiv->find('a.tgme_widget_message_photo_wrap', 0)) {
            $message .= $this->processPhoto($messageDiv);
        }

        if ($messageDiv->find('a.not_supported', 0)) {
            $message .= $this->processNotSupported($messageDiv);
        }

        if ($messageDiv->find('div.tgme_widget_message_text.js-message_text', 0)) {
            $message .= $messageDiv->find('div.tgme_widget_message_text.js-message_text', 0);

            $this->itemTitle = $this->ellipsisTitle(
                $messageDiv->find('div.tgme_widget_message_text.js-message_text', 0)->plaintext
            );
        }

        if ($messageDiv->find('div.tgme_widget_message_document', 0)) {
            $message .= $this->processAttachment($messageDiv);
        }

        if ($messageDiv->find('a.tgme_widget_message_link_preview', 0)) {
            $message .= $this->processLinkPreview($messageDiv);
        }

        if ($messageDiv->find('a.tgme_widget_message_location_wrap', 0)) {
            $message .= $this->processLocation($messageDiv);
        }

        return $message;
    }

    public function getURI()
    {
        $username = $this->getInput('username');
        if ($username) {
            return self::URI . '/s/' . $this->normalizeUsername();
        }
        return parent::getURI();
    }

    public function getName()
    {
        if ($this->feedName) {
            return $this->feedName . ' - Telegram';
        }
        return parent::getName();
    }

    private function processReply($messageDiv)
    {
        $reply = $messageDiv->find('a.tgme_widget_message_reply', 0);
        $author = $reply->find('span.tgme_widget_message_author_name', 0)->plaintext;
        $text = '';

        if ($reply->find('div.tgme_widget_message_metatext', 0)) {
            $text = $reply->find('div.tgme_widget_message_metatext', 0)->innertext;
        }

        if ($reply->find('div.tgme_widget_message_text', 0)) {
            $text = $reply->find('div.tgme_widget_message_text', 0)->innertext;
        }

        return <<<EOD
<blockquote>{$author}<br>
{$text}
<a href="{$reply->href}">{$reply->href}</a></blockquote><hr>
EOD;
    }

    private function processSticker($messageDiv)
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a sticker';
        }

        $stickerDiv = $messageDiv->find('div.tgme_widget_message_sticker_wrap', 0);

        if ($stickerDiv->find('picture', 0)) {
            $stickerDiv->find('picture', 0)->find('div', 0)->style = '';
            $stickerDiv->find('picture', 0)->style = '';

            return $stickerDiv;
        } elseif (preg_match(self::BACKGROUND_IMAGE_REGEX, $stickerDiv->find('i', 0)->style, $sticker)) {
            return <<<EOD
				<a href="{$stickerDiv->children(0)->herf}"><img src="{$sticker[1]}"></a>
EOD;
        }
    }

    private function processPoll($messageDiv)
    {
        $poll = $messageDiv->find('div.tgme_widget_message_poll', 0);

        $title = $poll->find('div.tgme_widget_message_poll_question', 0)->plaintext;
        $type = $poll->find('div.tgme_widget_message_poll_type', 0)->plaintext;

        if (!$this->itemTitle) {
            $this->itemTitle = $title;
        }

        $pollOptions = '<ul>';

        foreach ($poll->find('div.tgme_widget_message_poll_option') as $option) {
            $pollOptions .= '<li>' . $option->children(0)->plaintext . ' - ' .
                $option->find('div.tgme_widget_message_poll_option_text', 0)->plaintext . '</li>';
        }
        $pollOptions .= '</ul>';

        return <<<EOD
			{$title}<br><small>$type</small><br>{$pollOptions}
EOD;
    }

    private function processLinkPreview($messageDiv)
    {
        $image = '';
        $title = '';
        $site = '';
        $description = '';

        $preview = $messageDiv->find('a.tgme_widget_message_link_preview', 0);

        if (trim($preview->innertext) === '') {
            return '';
        }

        if (
            $preview->find('i', 0) &&
            preg_match(self::BACKGROUND_IMAGE_REGEX, $preview->find('i', 0)->style, $photo)
        ) {
            $image = '<img src="' . $photo[1] . '"/>';
        }

        if ($preview->find('div.link_preview_title', 0)) {
            $title = $preview->find('div.link_preview_title', 0)->plaintext;
        }

        if ($preview->find('div.link_preview_site_name', 0)) {
            $site = $preview->find('div.link_preview_site_name', 0)->plaintext;
        }

        if ($preview->find('div.link_preview_description', 0)) {
            $description = $preview->find('div.link_preview_description', 0)->plaintext;
        }

        return <<<EOD
<blockquote><a href="{$preview->href}">{$image}</a><br><a href="{$preview->href}">
{$title} - {$site}</a><br>{$description}</blockquote>
EOD;
    }

    private function processVideo($messageDiv)
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a video';
        }

        if ($messageDiv->find('i.tgme_widget_message_video_thumb')) {
            preg_match(self::BACKGROUND_IMAGE_REGEX, $messageDiv->find('i.tgme_widget_message_video_thumb', 0)->style, $photo);
        } elseif ($messageDiv->find('i.link_preview_video_thumb')) {
            preg_match(self::BACKGROUND_IMAGE_REGEX, $messageDiv->find('i.link_preview_video_thumb', 0)->style, $photo);
        } elseif ($messageDiv->find('i.tgme_widget_message_roundvideo_thumb')) {
            preg_match(self::BACKGROUND_IMAGE_REGEX, $messageDiv->find('i.tgme_widget_message_roundvideo_thumb', 0)->style, $photo);
        } else {
            // Not all videos have a poster image
            $photo = [null, null];
        }

        $this->enclosures[] = $photo[1];

        // Intentionally omitting preload="none" on <video>
        return <<<EOD
<video controls="" poster="{$photo[1]}" style="max-width:100%;">
	<source src="{$messageDiv->find('video', 0)->src}" type="video/mp4">
</video>
EOD;
    }

    private function processPhoto($messageDiv)
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a photo';
        }

        $photos = '';

        foreach ($messageDiv->find('a.tgme_widget_message_photo_wrap') as $photoWrap) {
            preg_match(self::BACKGROUND_IMAGE_REGEX, $photoWrap->style, $photo);

            $photos .= <<<EOD
<a href="{$photoWrap->href}"><img src="{$photo[1]}"/></a><br>
EOD;
        }
        return $photos;
    }

    private function processNotSupported($messageDiv)
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a video';
        }

        if ($messageDiv->find('i.tgme_widget_message_video_thumb')) {
            preg_match(self::BACKGROUND_IMAGE_REGEX, $messageDiv->find('i.tgme_widget_message_video_thumb', 0)->style, $photo);
        } elseif ($messageDiv->find('i.link_preview_video_thumb')) {
            preg_match(self::BACKGROUND_IMAGE_REGEX, $messageDiv->find('i.link_preview_video_thumb', 0)->style, $photo);
        } else {
            // Unsupported content ususally don't have a preview image
            $photo = [null, null];
        }

        return <<<EOD
<a href="{$messageDiv->find('a.not_supported', 0)->href}">
{$messageDiv->find('div.message_media_not_supported_label', 0)->innertext}<br><br>
{$messageDiv->find('span.message_media_view_in_telegram', 0)->innertext}<br><br>
<img src="{$photo[1]}"/></a>
EOD;
    }

    private function processAttachment($messageDiv)
    {
        $attachments = 'File attachments:<br>';

        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted an attachment';
        }

        foreach ($messageDiv->find('div.tgme_widget_message_document') as $document) {
            $attachments .= <<<EOD
{$document->find('div.tgme_widget_message_document_title', 0)->plaintext} -
{$document->find('div.tgme_widget_message_document_extra', 0)->plaintext}<br>
EOD;
        }

        return $attachments;
    }

    private function processLocation($messageDiv)
    {
        if (!$this->itemTitle) {
            $this->itemTitle = '@' . $this->normalizeUsername() . ' posted a location';
        }

        preg_match(self::BACKGROUND_IMAGE_REGEX, $messageDiv->find('div.tgme_widget_message_location', 0)->style, $image);

        $link = $messageDiv->find('a.tgme_widget_message_location_wrap', 0)->href;

        return <<<EOD
			<a href="{$link}"><img src="{$image[1]}"></a>
EOD;
    }

    // todo: extract truncate
    private function ellipsisTitle($text)
    {
        $length = 100;
        if (strlen($text) > $length) {
            $text = explode('<br>', wordwrap($text, $length, '<br>'));
            return $text[0] . '...';
        }
        return $text;
    }

    private function normalizeUsername()
    {
        // todo: can be replaced with ltrim($username, '@');
        $username = $this->getInput('username');
        if (substr($username, 0, 1) === '@') {
            return substr($username, 1);
        }
        return $username;
    }

    public function detectParameters($url)
    {
        $detectParamsRegex = '/^https?:\/\/(?:(?:t|telegram)\.me\/(?:s\/)?([\w]+)|([\w]+)\.t\.me\/?)$/';
        $params = [];
        if (preg_match($detectParamsRegex, $url, $matches) > 0) {
            if ($matches[1] !== '') {
                $params['username'] = $matches[1];
            }

            if (isset($matches[2]) && $matches[2] !== '') {
                $params['username'] = $matches[2];
            }

            return $params;
        }
        return null;
    }
}
