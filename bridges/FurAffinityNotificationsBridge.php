<?php

class FurAffinityNotificationsBridge extends BridgeAbstract
{
    const NAME = 'FurAffinity User Pages Bridge';
    const URI = 'https://www.furaffinity.net/';
    const CACHE_TIMEOUT = 900; // 15min
    const MAINTAINER = 'mrauc';
    const DESCRIPTION = 'User pages from FurAffinity.net';
    const CONFIGURATION = [
        'aCookie' => [
            'required' => true
        ],
        'bCookie' => [
            'required' => true
        ]
    ];
    const PARAMETERS = [
        [
            'submissions' => [
                'name' => 'New submissions from your watching list',
                'type' => 'checkbox',
                'required' => false
            ],
            'watches' => [
                'name' => 'New watchers',
                'type' => 'checkbox',
                'required' => false
            ],
            'comments' => [
                'name' => 'Comments and Shouts',
                'title' => 'Comment activity on your comments, submissions and journals, and shouts on your profile.',
                'type' => 'checkbox',
                'required' => false
            ],
            'favourites' => [
                'name' => 'Favourites on your submissions',
                'type' => 'checkbox',
                'required' => false
            ],
            'journals' => [
                'name' => 'Journals from your watching list',
                'type' => 'checkbox',
                'required' => false
            ],
            'notes' => [
                'name' => 'Unread received notes',
                'type' => 'checkbox',
                'required' => false
            ]

            // 'tickets' => [
            //     'name' => 'Updates on your tickets',
            //     'type' => 'checkbox',
            //     'required' => false,
            // ]
        ]
    ];
    private $FA_AUTH_COOKIE;
    const EMOJIS = [
        '😛', '😎', '😉', '😲',
        '🙂', '😈', '😵', '😏',
        '😇', '🤡', '🤣', '💿',
        '😭', ':/', '😳', '🎁',
        '🍺', '❤', '🤓', '🎶',
        '🤪', '🤨', '😐', '🙁',
        '🥱', '😠', '😃', '😡',
        '🤐'
    ];
    const SMILEYS = [
        '<i class="smilie tongue"></i>',
        '<i class="smilie cool"></i>',
        '<i class="smilie wink"></i>',
        '<i class="smilie oooh"></i>',
        '<i class="smilie smile"></i>',
        '<i class="smilie evil"></i>',
        '<i class="smilie huh"></i>',
        '<i class="smilie whatever"></i>',
        '<i class="smilie angel"></i>',
        '<i class="smilie badhairday"></i>',
        '<i class="smilie lmao"></i>',
        '<i class="smilie cd"></i>',
        '<i class="smilie crying"></i>',
        '<i class="smilie dunno"></i>',
        '<i class="smilie embarrassed"></i>',
        '<i class="smilie gift"></i>',
        '<i class="smilie coffee"></i>',
        '<i class="smilie love"></i>',
        '<i class="smilie nerd"></i>',
        '<i class="smilie note"></i>',
        '<i class="smilie derp"></i>',
        '<i class="smilie sarcastic"></i>',
        '<i class="smilie serious"></i>',
        '<i class="smilie sad"></i>',
        '<i class="smilie sleepy"></i>',
        '<i class="smilie teeth"></i>',
        '<i class="smilie veryhappy"></i>',
        '<i class="smilie yelling"></i>',
        '<i class="smilie zipped"></i>'
    ];

    public function collectData()
    {
        $this->FA_AUTH_COOKIE = 'b=' . $this->getOption('bCookie') . '; a=' . $this->getOption('aCookie');
        $submissions = $this->getInput('submissions');
        $watches = $this->getInput('watches');
        $comments = $this->getInput('comments');
        $favourites = $this->getInput('favourites');
        $journals = $this->getInput('journals');
        $notes = $this->getInput('notes');
        // $tickets = $this->getInput('tickets');

        //parse new submissions page
        if ($submissions) {
            $url = self::URI . 'msg/submissions/new';
            $html = $this->getFASimpleHTMLDOM($url)
                or returnServerError('Could not load your New Submissions page. Check your cookies?');

            $oldUI = $this->isOldUI($html);

            if ($oldUI) {
                $submission_elem = $html->find('.notification-galleries figure');
            } else {
                $submission_elem = $html->find('#messagecenter-submissions figure');
            }
            foreach ($submission_elem as $submission) {
                $id = substr($submission->getAttribute('id'), 4);
                $uri = 'view/' . $id;
                $this->addItem($this->getSubmission($uri, $oldUI));
            }
        }

        //parse notifications page
        if ($watches || $comments || $favourites || $journals) {
            $url = self::URI . 'msg/others/';
            $html = $this->getFASimpleHTMLDOM($url)
                or returnServerError('Could not load your notifications page. Check your cookies?');

            $oldUI = $this->isOldUI($html);

            if ($watches) {
                if ($oldUI) {
                    $watchers = $html->find('#watches li');
                } else {
                    $watchers = $html->find('#messages-watches .message-stream li');
                }

                foreach ($watchers as $watcher) {
                    if ($watcher->hasClass('section-controls')) {
                        break;
                    }
                    $user = $watcher->find('.info span', 0)->plaintext;
                    $date = $watcher->find('.info span', 1)->getAttribute('title');
                    $avatar = $watcher->find('img', 0)->getAttribute('src');
                    $url = self::URI . 'user/' . $user;

                    $this->addItem([
                        'title' => 'New watcher: ' . $user,
                        'uri' => $url,
                        'uid' => $url,
                        'timestamp' => $date,
                        'content' => "<p><a href=\"{$url}\"> <img src=\"{$avatar}\" referrerpolicy=\"no-referrer\" /><b>$user</b></a> is a new watcher.</p>",
                        'categories' => ['watchers']
                    ]);
                }
            }

            //submission / journal comments / shouts notification
            if ($comments) {
                if ($oldUI) {
                    $current_user = substr(trim($html->find('#my-username', 0)->plaintext, ' \\n\n\r\t\v\x00'), 1);
                    foreach ($html->find('fieldset#messages-comments-submission li') as $submission_comment) {
                        if ($submission_comment->hasClass('section-controls')) {
                            break;
                        }
                        $this->addItem($this->parseCommentNotif($submission_comment, $oldUI));
                    }
                    foreach ($html->find('fieldset#messages-comments-journal li') as $journal_comment) {
                        if ($journal_comment->hasClass('section-controls')) {
                            break;
                        }
                        $this->addItem($this->parseCommentNotif($journal_comment, $oldUI));
                    }
                    foreach ($html->find('fieldset#messages-shouts li') as $shout) {
                        if ($shout->hasClass('section-controls')) {
                            break;
                        }
                        $this->addItem($this->parseCommentNotif($shout, $oldUI, $current_user));
                    }
                } else {
                    $current_user = $html->find('.loggedin_user_avatar', 0);
                    $current_user = $current_user ? $current_user->getAttribute('alt') : null;
                    foreach ($html->find('#messages-comments-submission li') as $submission_comment) {
                        $this->addItem($this->parseCommentNotif($submission_comment, $oldUI));
                    }
                    foreach ($html->find('#comments-journal li') as $journal_comment) {
                        $this->addItem($this->parseCommentNotif($journal_comment, $oldUI));
                    }
                    foreach ($html->find('#messages-shouts li') as $shout) {
                        $this->addItem($this->parseCommentNotif($shout, $oldUI, $current_user));
                    }
                }
            }

            if ($favourites) {
                //same for both old and new ui
                foreach ($html->find('#messages-favorites li') as $favourite) {
                    if ($favourite->hasClass('section-controls')) {
                        break;
                    }
                    $this->addItem($this->parseFavourites($favourite));
                }
            }

            if ($journals) {
                if ($oldUI) { //new journals (old UI)
                    foreach ($html->find('#journals li') as $journal) {
                        if ($journal->hasClass('section-controls')) {
                            break;
                        }
                        $this->addItem($this->parseJournals($journal, $oldUI));
                    }
                } else { //new journals (new UI)
                    foreach ($html->find('#messages-journals li') as $journal) {
                        $this->addItem($this->parseJournals($journal, $oldUI));
                    }
                }
            }
        }

        //parse unread notes
        if ($notes) {
            $url = self::URI . 'controls/switchbox/unread/';
            $html = $this->getFASimpleHTMLDOM($url)
                or returnServerError('Could not load your unread Notes page. Check your cookies?');

            $oldUI = $this->isOldUI($html);

            $unreads = $html->find('.note-unread');
            foreach ($unreads as $unread) {
                $this->addItem($this->getNote($unread, $oldUI));
            }
        }
    }

    private function getNote($record, $isOldUI)
    {
        $url = $record->getAttribute('href');
        //NOTE: Opening a note automatically marks it as read.
        $html = $this->getFASimpleHTMLDOM($url, true)
            or returnServerError("Could not load {$url}. Check your cookies?");

        if ($isOldUI) { //https://www.furaffinity.net/viewmessage/{$id}/
            $id = $html->find('#pms-form [name^="items"]', 0)->getAttribute('value');
            $title = $html->find('#pms-form .maintable tr', 0)->find('font b', 0)->plaintext;
            $note = $html->find('#pms-form .maintable tr', 1);
            $from = $note->find('font a', 0)->plaintext;
            $time = $note->find('.popup_date', 0)->getAttribute('title');
            $note->find('font', 0)->remove();
            $content = trim($this->formatComment($note));
        } else { //https://www.furaffinity.net/msg/pms/1/{$id}/
            $id = $html->find('#note-actions [name^="items"]', 0)->getAttribute('value');
            $note = $html->find('#message', 0);
            $title = $note->find('.section-header h2', 0)->plaintext;
            $from = $note->find('.addresses strong', 0)->plaintext;
            $time = $note->find('.popup_date', 0)->getAttribute('title');
            $content = $this->formatComment($note->find('.user-submitted-links', 0));
        }
        $content .= "<hr/><a href=\"{$url}\">Mark note as read</a>";

        //Mark note as unread.
        getContents(
            'https://www.furaffinity.net/msg/pms/',
            ['Cookie: ' . $this->FA_AUTH_COOKIE],
            [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => "manage_notes=1&move_to=unread&items%5B%5D={$id}"
            ]
        );

        return [
            'uri' => $url,
            'title' => $title,
            'timestamp' => $time,
            'author' => $from,
            'content' => $content,
            'categories' => ['notes'],
            'uid' => self::URI . "viewmessage/{$id}/"
        ];
    }

    private function parseFavourites($record)
    {
        //same for both old and new ui
        $user = $record->find('a', 0);
        $submission = $record->find('a', 1);
        $uid = $record->find('input', 0)->getAttribute('value');
        $item = [];
        $item['categories'] = ['favorites'];
        $item['title'] = "{$user->plaintext} has favourited: {$submission->plaintext}";
        $item['uri'] = $user->getAttribute('href');
        $item['uid'] = "{$user->getAttribute('href')}#favorites-{$uid}";
        $item['timestamp'] = $record->find('.popup_date', 0)->getAttribute('title');
        $item['content'] = "<b><a href=\"{$user->getAttribute('href')}\">{$user->plaintext}</a></b>";
        $item['content'] .= " has favourited your submission: <a href=\"{$submission->getAttribute('href')}\">{$submission->plaintext}</a>";
        return $item;
    }

    private function parseCommentNotif($record, $oldUI, $current_user = null)
    {
        if (
            $record->plaintext === 'Shout has been removed from your page.'
            || $record->plaintext === 'Comment or the Submission it was left on has been deleted.'
            || $record->plaintext === 'Comment or the Journal it was left on has been deleted.'
        ) {
            return null;
        }

        $type = $record->find('input', 0)->getAttribute('name');
        switch ($type) {
            case 'shouts[]':
                $type = 'shout';
                $url = self::URI . "user/{$current_user}#shout-{$record->find('input', 0)->getAttribute('value')}";
                break;
            case 'comments-submissions[]':
                $type = 'submission';
                $url = $record->find('a', 1);
                $url = $url ? $url->getAttribute('href') : null;
                break;
            case 'comments-journals[]':
                $type = 'journal';
                $url = $record->find('a', 1);
                $url = $url ? $url->getAttribute('href') : null;
                break;
        }

        $cid = parse_url($url)['fragment'];
        $who = $record->find('a', 0)->plaintext;
        $title = '';
        $content = '';
        $isDisabled = false;
        $isDeleted = false;
        $date = substr($record->find('.popup_date', 0)->getAttribute('title'), 3);

        if ($type != 'shout') { //journal / submission comment
            $html = $this->getFASimpleHTMLDOM($url, true)
                or returnServerError("Could not load {$url}. Check your cookies?");
            $isDisabled = $this->checkDisabled($html); //disabled user preventing journal access
            if (!$isDisabled) {
                if ($oldUI) {
                    $comment = $html->find("[id='$cid']", 0);
                    if (is_null($comment->find('.comment-deleted', 0))) {
                        $content = $this->formatComment($comment->find('.message-text', 0));
                    } else {
                        $isDeleted = true;
                        $content = '<p>Comment deleted</p>';
                    }
                } else {
                    $comment = $html->find("[id='$cid']", 0)->parent();
                    if (is_null($comment->find('.deleted-comment-container', 0))) {
                        $content = $this->formatComment($comment->find('.user-submitted-links', 0));
                    } else {
                        $isDeleted = true;
                        $content = '<p>Comment deleted</p>';
                    }
                }
            } else {
                $content = 'Journal hidden due to disabled account.';
            }

            $post_title = $record->find('a', 1)->plaintext;
            if (str_contains($record->plaintext, 'replied to your comment on')) { //new (comment|reply) on (their|your|a) (submission|journal): XXX
                if (str_contains($record->plaintext, 'replied to your comment on a')) { //on 3rd party post
                    $title = "{$who} replied to your comment on a {$type}: {$post_title}";
                } elseif (str_contains($record->plaintext, 'replied to your comment on their')) { //on OP's post
                    $title = "{$who} replied to your comment on their {$type}: {$post_title}";
                } else { //on your post
                    if ($oldUI) {
                        $title = "{$who} replied to your comment on your {$type}: {$post_title}";
                    } else {
                        $title = "{$who} replied to your comment on {$type}: {$post_title}";
                    }
                }
                //include "in reply to here"
                if (!$isDisabled && !$isDeleted) {
                    if ($oldUI) {
                        $parent_comment = $this->findParentComment($comment, $oldUI);
                        $parent_name = $parent_comment->find('.replyto-name', 0)->plaintext;
                        $parent_content = $parent_comment->find('.message-text', 0);
                    } else {
                        $parent_comment = $this->findParentComment($comment, $oldUI);
                        $parent_name = $parent_comment->find('.comment_username', 0)->plaintext;
                        $parent_content = $parent_comment->find('.user-submitted-links', 0);
                    }
                    $content .= "<br/><table style=\"border: 1px solid;\"><tr><td><b>Replying to {$parent_name}:</b>";
                    $content .= "<blockquote>{$this->formatComment($parent_content)}</blockquote></td></tr></table>";
                }
            } else {
                $title = "{$who} replied to your {$type}: {$post_title}"; //initial comment
            }
        } else { //shout
            $title = "{$who} left a shout on your profile";
            $html = $this->getFASimpleHTMLDOM($url, true)
                or returnServerError("Could not load {$url}. Check your cookies?");
            if ($oldUI) {
                $content = $this->formatComment($html->find("[id='$cid'] .no_overflow", 0));
            } else {
                $content = $this->formatComment($html->find("[id='$cid']", 0)->parent()->find('.user-submitted-links', 0));
            }
        }

        return [
            'categories' => ["{$type}_comment"],
            'author' => $who,
            'title' => $title,
            'uri' => $url,
            'uid' => $url,
            'timestamp' => $date,
            'content' => $content
        ];
    }

    private function parseJournals($record, $oldUI)
    {
        $id = $record->find('input', 0)->getAttribute('value');
        $url = self::URI . 'journal/' . $id;
        $html = $this->getFASimpleHTMLDOM($url, true)
            or returnServerError('Could not load ' . $url);

        $item = [
            'uid' => $url,
            'uri' => $url,
            'categories' => ['journal']
        ];

        if ($this->checkDisabled($html)) {
            $item['title'] = $record->find('a', 0)->plaintext;
            $item['timestamp'] = substr($record->find('.popup_date', 0)->getAttribute('title'), 3);
            $item['author'] = $record->find('a', 1)->plaintext;
            $item['content'] = 'Journal hidden due to disabled account.';
            return $item;
        }


        if ($oldUI) {
            $header = $this->formatComment($html->find('.journal-header', 0));
            $content = $this->formatComment($html->find('.journal-body', 0));
            $footer = $this->formatComment($html->find('.journal-footer', 0));

            if (!is_null($header)) {
                $header .= '</hr>';
            }
            if (!is_null($footer)) {
                $content .= '</hr>';
            }
            $content = $header . $content . $footer;

            $item['title'] = trim($html->find('.journal-title-box .no_overflow', 0)->plaintext);
            $item['timestamp'] = $html->find('.journal-title-box .popup_date', 0)->getAttribute('title');
            $item['author'] = $html->find('td.cat .journal-title-box a', 0)->plaintext;
            $item['content'] = $content;
        } else {
            $header = $this->formatComment($html->find('#columnpage .journal-header', 0));
            $content = $this->formatComment($html->find('#columnpage .journal-content-container', 0));
            $footer = $this->formatComment($html->find('#columnpage .journal-footer', 0));

            if (!is_null($header)) {
                $header .= '</hr>';
            }
            if (!is_null($footer)) {
                $content .= '</hr>';
            }
            $content = $header . $content . $footer;

            $item['title'] = $html->find('#columnpage .section-header .journal-title', 0)->plaintext;
            $item['timestamp'] = $html->find('#columnpage .section-header .popup_date', 0)->getAttribute('title');
            $item['author'] = substr(trim($html->find('username', 0)->plaintext, ' \\n\n\r\t\v\x00'), 1);
            $item['content'] = $content;
        }

        return $item;
    }

    private function formatComment($elem)
    {
        if (is_null($elem)) {
            return null;
        }

        //format quotes into blockquotes
        $quotes = $elem->find('.bbcode_quote');
        foreach ($quotes as $quote) {
            $name = $quote->find('.bbcode_quote_name', 0);
            if (!is_null($name)) {
                $name = "<b>{$name->plaintext}</b>";
                $quote->find('.bbcode_quote_name', 0)->remove();
            }
            $quote->outertext = "<table style=\"border: 1px solid;\"><tr><td>{$name}<blockquote>{$quote->innertext}</blockquote></td></tr></table>";
        }

        foreach ($elem->find('img') as $img) {
            /* From FurAffinityBridge by roliga:
             * Note: Without the no-referrer policy their CDN sometimes denies requests.
             * We can't control this for enclosures sadly.
             * At least tt-rss adds the referrerpolicy on its own.
             * Alternatively we could not use https for images, but that's not ideal.
             */
            $img->referrerpolicy = 'no-referrer';
        }

        return str_replace(self::SMILEYS, self::EMOJIS, $elem); //converts it to string
    }

    protected function getSubmission($uri, $isOldUI)
    {
        $url = self::URI . $uri;
        $html = $this->getFASimpleHTMLDOM($url, true)
            or returnServerError('Could not load: ' . $uri . '- Check your cookies?');

        if ($this->checkRequireMature($html)) {
            return null;
        }

        $item = [];
        $item['uri'] = $url;
        $item['uid'] = $url;
        $item['categories'] = ['submission'];
        $imgURL = 'https:' . $html->find('#submissionImg', 0)->getAttribute('data-fullview-src'); // Array of URIs to an attachments (pictures, files, etc...)
        $item['content'] = "<a href=\"{$url}\"> <img src=\"{$imgURL}\" referrerpolicy=\"no-referrer\" /></a>";

        if ($isOldUI) {
            $item['title'] = $html->find('.classic-submission-title .information h2', 0)->plaintext; // Title of the item
            $item['timestamp'] = $html->find('.stats-container .popup_date', 0)->getAttribute('title');        // Timestamp of the item in numeric or text format (compatible for strtotime())
            $item['author'] = $html->find('.classic-submission-title .information a', 0)->plaintext; // Name of the author for this item
            $item['content'] .= $this->formatComment($html->find('.maintable', 1)->find('td', 3)); // Content in HTML format
            $item['content'] .= '<hr/><b>Tags:</b><br/>';
            foreach ($html->find('#keywords a') as $tag) {
                $item['content'] .= "{$tag->outertext}<br/>";
            }
        } else {
            $item['title'] = trim($html->find('.submission-title', 0)->plaintext); // Title of the item
            $item['timestamp'] = $html->find('.submission-id-sub-container .popup_date', 0)->getAttribute('title');        // Timestamp of the item in numeric or text format (compatible for strtotime())
            $item['author'] = $html->find('.submission-id-sub-container strong', 0)->plaintext; // Name of the author for this item
            $item['content'] .= $this->formatComment($html->find('.submission-description', 0)); // Content in HTML format
            $item['content'] .= '<hr/><b>Tags:</b><br/>';
            foreach ($html->find('.section-body .tags a') as $tag) {
                $item['content'] .= "{$tag->outertext}<br/>";
            }
        }

        return $item;
    }

    private function isOldUI($html)
    {
        $isOldUI = $html->find('body', 0)->getAttribute('data-static-path') === '/themes/classic';

        $current_user = null;
        if ($isOldUI) {
            $current_user = substr(trim($html->find('#my-username', 0)->plaintext, ' \\n\n\r\t\v\x00'), 1);
        } else {
            $current_user = $html->find('.loggedin_user_avatar', 0);
            $current_user = $current_user ? $current_user->getAttribute('alt') : null;
        }
        $this->saveCacheValue('username', $current_user);

        return $isOldUI;
    }

    private function checkDisabled($html)
    {
        //You can choose to disable your account. Disabling your account prevents your userpage, gallery, favorites and JOURNALS from being viewed by others. While disabled, you will not be able to post or submit content.

        return $html->find('title', 0)->plaintext === 'Account disabled. -- Fur Affinity [dot] net';
    }

    private function checkRequireMature($html)
    {
        $system_message = $html->find('.section-body', 0);
        $system_message = $system_message ? $system_message->plaintext : '';

        return str_contains($system_message, 'To view this submission you must log in');
    }

    private function findParentComment($comment, $oldUI)
    {
        //get parent comment's CID of the current comment
        if ($oldUI) {
            preg_match('/cid:\d+/', $comment->find('.comment-parent', 0)->href, $res);
            $parent_cid = $res[0];
        } else {
            preg_match('/cid:\d+/', $comment->find('comment', 0), $res);
            $parent_cid = $res[0];
        }

        //get CID of provided comment
        $getCID = function ($elem) use ($oldUI) {
            if ($oldUI) {
                return $elem->getAttribute('id');
            } else {
                return $elem->find('a', 0)->getAttribute('id');
            }
        };

        $comment_sibling = $comment->prev_sibling();
        while ($parent_cid !== $getCID($comment_sibling)) {
            $comment_sibling = $comment_sibling->prev_sibling();
        }
        return $comment_sibling;
    }

    //From Roliga's FurAffinityBridge
    private function getFASimpleHTMLDOM($url, $cache = false)
    {
        $header = [
            'Cookie: ' . $this->FA_AUTH_COOKIE
        ];

        if ($cache) {
            $html = getSimpleHTMLDOMCached($url, 86400, $header); // 24 hours
        } else {
            $html = getSimpleHTMLDOM($url, $header);
        }

        $html = defaultLinkTo($html, $url);

        return $html;
    }

    private function addItem($item)
    {
        if (is_null($item)) {
            return;
        }

        if (is_array($item) || is_object($item)) {
            $this->items[] = $item;
        } else {
            returnServerError("Incorrectly parsed item. Check the code!\nType: " . gettype($item) . "\nprint_r(item:)\n" . print_r($item));
        }
    }

    public function getName()
    {
        $username = $this->loadCacheValue('username');
        if (isset($username)) {
            return self::NAME . ' for ' . $username;
        } else {
            return self::NAME;
        }
    }
}
