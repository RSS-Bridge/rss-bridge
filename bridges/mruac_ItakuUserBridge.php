<?php

class mruac_ItakuUserBridge extends BridgeAbstract
{
    const NAME = 'Itaku.ee User Bridge';
    const URI = 'https://itaku.ee';
    const CACHE_TIMEOUT = 900; // 15mn
    const MAINTAINER = 'mruac';
    const DESCRIPTION = 'Bridges for Authenticated Itaku.ee User pages';
    const PARAMETERS = [
        'Notifications' => [
            'messages' => [
                'name' => 'Incoming unread messages',
                'type' => 'checkbox',
                'required' => false
            ],
            'incomm' => [
                'name' => 'Incoming commission requests',
                'type' => 'checkbox',
                'required' => false
            ],
            'tags' => [
                'name' => 'Incoming Tag/Maturity suggestions on your images',
                'type' => 'checkbox',
                'required' => false
            ],
            'submissions' => [
                'name' => 'Followed Submissions (Use Home Feed bridge for shares)',
                'type' => 'checkbox',
                'required' => false
            ]
            //     'stars' => [
            //         'name' => 'Stars on your images / posts / comments',
            //         'type' => 'checkbox',
            //         'required' => false,
            //     ],
            //     'comments' => [
            //         'name' => 'Comments and replies to you',
            //         'type' => 'checkbox',
            //         'required' => false,
            //     ],
            //     'mentions' => [
            //         'name' => 'Mentions of you across Itaku',
            //         'type' => 'checkbox',
            //         'required' => false,
            //     ],
            //     'other' => [
            //         'name' => 'All other notifications: New follows, Commission activity, etc.',
            //         'type' => 'checkbox',
            //         'required' => false,
            //     ]
        ],
        'Home feed of your following' => [
            'reshares' => [
                'name' => 'Include reshares',
                'type' => 'checkbox',
            ],
            'rating_s' => [
                'name' => 'Include SFW',
                'type' => 'checkbox',
            ],
            'rating_q' => [
                'name' => 'Include Questionable',
                'type' => 'checkbox',
            ],
            'rating_e' => [
                'name' => 'Include NSFW',
                'type' => 'checkbox',
            ]
        ]
    ];
    const CONFIGURATION = [
        'auth' => [
            'required' => true
        ]
    ];
    private $token;

    public function collectData()
    {
        $this->token = $this->getOption('auth');

        $user_profile = $this->loadCacheValue('userprofile');
        if (!isset($user_profile)) {
            $user_profile = $this->getData(self::URI . '/api/auth/user/?format=json', false, true);
            $this->saveCacheValue('userprofile', $user_profile);
        }

        if ($this->queriedContext === 'Notifications') {

            $messages = $this->getInput('messages');
            $incomm = $this->getInput('incomm');
            $tags = $this->getInput('tags');
            $submissions = $this->getInput('submissions');
            // $stars = $this->getInput('stars');
            // $comments = $this->getInput('comments');
            // $mentions = $this->getInput('mentions');
            // $other = $this->getInput('other');

            $user_profile = $this->getData(self::URI . '/api/auth/user/?format=json', true, true);

            //Messages
            if ($messages) {
                $data = $this->getData(self::URI . '/api/messenger/chats/?page_size=20&page=1&format=json', false, true);
                foreach ($data['results'] as $record) {
                    $this->getMessage($record, $user_profile['profile']['owner']);
                }
            }

            //Inbox
            if ($incomm || $tags || $submissions) {
                //incoming commission requests
                if ($incomm) {
                    $data = $this->getData(self::URI . '/api/commission_join_requests/?inbox=RECEIVED&page_size=20&format=json', false, true);
                    foreach ($data['results'] as $record) {
                        $this->addItem($this->getIncomingCommission($record));
                    }
                }

                //incoming tag/maturity suggestions
                if ($tags) {
                    $data = $this->getData(self::URI . '/api/tag_suggestions/?&inbox=RECEIVED&unresolved=false&page_size=20&format=json', false, true);
                    foreach ($data['results'] as $record) {
                        $this->addItem($this->getTagSuggestions($record));
                    }
                }

                //submitted images/posts/commissions by following
                if ($submissions) {
                    $data = $this->getData(self::URI . '/api/submission_inbox/?&page=1&page_size=20&format=json', false, true);
                    foreach ($data['page']['results'] as $record) {
                        $this->addItem($this->getSubmissionInbox($record));
                    }
                }
            }

            //     //Notifications
            // if ($stars || $comments || $mentions || $other) {
            //     //open the notificatioins bell to get the notifications network activity
            //     $opt = [
            //         'stars' => $stars,
            //         'comments' => $comments,
            //         'mentions' => $mentions,
            //         'other' => $other
            //     ];
            //     $data = $this->getData(self::URI . '/api/notifications/?&page=1&page_size=20&format=json', false, true);
            //     foreach ($data['results'] as $record) {
            //         //only adds the latest unread message to $items[]
            //         $this->addItem($this->getNotifications($record, $opt));
            //     }
            // }
        }

        if ($this->queriedContext === 'Home feed of your following') {

            $opt = [
                'order' => $this->getInput('order'),
                'range' => $this->getInput('range'),
                'reshares' => $this->getInput('reshares'),
                'rating_s' => $this->getInput('rating_s'),
                'rating_q' => $this->getInput('rating_q'),
                'rating_e' => $this->getInput('rating_e')
            ];

            $data = $this->getFeed($opt);

            foreach ($data['results'] as $record) {
                switch ($record['content_type']) {
                    case "reshare": {
                            //get type of reshare and its id
                            $id = $record['content_object']['content_object']['id'];
                            switch ($record['content_object']['content_type']) {
                                case "galleryimage": {
                                        $item = $this->getImage($id);
                                        $item['title'] = "{$record['owner_username']} shared: {$item['title']}";
                                        break;
                                    }
                                case "commission": {
                                        $item = $this->getCommission($id, $record['content_object']['content_object']);
                                        $item['title'] = "{$record['owner_username']} shared: {$item['title']}";
                                        break;
                                    }
                                case "post": {
                                        $item = $this->getPost($id, $record['content_object']['content_object']);
                                        $item['title'] = "{$record['owner_username']} shared: {$item['title']}";
                                        break;
                                    }
                            }
                            break;
                        }
                    case "galleryimage": {
                            $item = $this->getImage($record['content_object']['id']);
                            break;
                        }
                    case "commission": {
                            $item = $this->getCommission($record['content_object']['id'], $record['content_object']);
                            break;
                        }
                    case "post": {
                            $item = $this->getPost($record['content_object']['id'], $record['content_object']);
                            break;
                        }
                }

                $this->addItem($item);
            }
        }
    }

    /*
    private function getNotifications(array $record, array $opt)
    {
        $title = $record['actor_displayname'];
        $content = '';
        $urls = $this->resolveNotificationURL($record, $record['content_type'], $record['object_id']);
        if (is_null($urls['url'])) {$url = $urls['host']['url'];} else {$url = $urls['url'];}

        switch ($record['action_type']) {
                //NOTIFICATIONS WILL NOT GIVE YOU METADATA / CONTENT_OBJECT LIKE A SEARCH DOES. YOU MUST FETCH MORE DETAILS.
            case 'liked': {
                    if ($opt['stars']) {
                        if (array_key_exists('comment_host_content_type', $record) && $record['comment_host_content_type'] === 'galleryimage') {
                            $record['comment_host_content_type'] = 'image';
                        }

                        //user starred your comment on: image/post/commission title
                        //user starred your image/post/commission: title
                        $record['action_type'] = 'starred';
                        $title .=  " starred your {$record['content_type']}";
                        if ($record['content_type'] === 'comment') {
                            $title .= ' on ' . $record['comment_host_content_type'];
                        }

                        $title .= ': ';

                        $a = $this->getNotificationContent($record['content_type'], $record['object_id'], $record);
                        $title .= $a['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'reshared': {
                    if ($opt['stars']) {
                        //user reshared your post/image/comment; title
                        $title .=  " reshared your {$record['content_type']}: ";
                        $title .= $this->getNotificationContent($record['content_type'], $record['object_id'])['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'commented': {
                    if ($opt['comments']) {
                        $notif_data = $this->getNotificationContent($record['content_type'], $record['object_id'], $record);
                        if ($record['content_type'] === 'comment') {
                            //user (replied to your comment)(commented) on commission/image/post: {title}
                            if ($record['comment_host_content_type'] === 'galleryimage') {
                                $record['comment_host_content_type'] = 'image';
                            }
                            $title .=  " replied to your comment on {$record['comment_host_content_type']}: ";
                        } else {
                            if ($record['content_type'] === 'galleryimage') {
                                $record['content_type'] = 'image';
                            }
                            $title .=  " commented on your {$record['content_type']}: ";
                        }

                        $title .= $notif_data['title'];
                        $content = $this->getComment($record['object_id']) . $notif_data['content'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'mentioned': {
                    if ($opt['mentions']) {
                        $notif_data = $this->getNotificationContent($record['content_type'], $record['object_id']);
                        //user mention you in their content_type: title
                        $title .=  " mentioned you in their {$record['content_type']}: ";
                        $title .= $notif_data['title'];
                        $content = $notif_data['content'];
                    } else {
                        return null;
                    }
                    break;
                }

            case 'followed': {
                    if ($opt['other']) {
                        //user followed you!
                        $title .=  " followed you!";
                    } else {
                        return null;
                    }
                    break;
                }
            case 'bid': {
                    if ($opt['other']) {

                        //user placed a bid on your content_type: title
                        $title .=  " placed a bid on your commission: ";
                        $title .= $this->getNotificationContent($record['content_type'], $record['object_id'])['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'slotted': {
                    if ($opt['other']) {

                        //user added you into their  commission slot for: titke
                        $title .=  " added you into their commission slot for: ";
                        $title .= $this->getNotificationContent($record['content_type'], $record['object_id'])['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'deslotted': {
                    if ($opt['other']) {

                        //user removed you from their commission slot for: title
                        $title .=  " removed you from their commission slot for: ";
                        $title .= $this->getNotificationContent($record['content_type'], $record['object_id'])['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'opened_comms': {
                    if ($opt['other']) {

                        //user opened a commission: title
                        $title .=  " opened a commission: ";
                        $title .= $this->getNotificationContent($record['content_type'], $record['object_id'])['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'accepted_comm_join_request': {
                    if ($opt['other']) {

                        //user accepted your commission join request for: comm title
                        $title .=  " accepted your commission join request for: ";
                        $title .= $this->getNotificationContent($record['content_type'], $record['object_id'])['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'declined_comm_join_request': {
                    if ($opt['other']) {

                        //user declined your commission join request for: comm title
                        $title .=  " declined your commission join request for: ";
                        $title .= $this->getNotificationContent($record['content_type'], $record['object_id'])['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'canceled_comm_join_request': {
                    if ($opt['other']) {

                        //user canceled their commission join requedt for: commtitle
                        $title .=  " canceled their commission join request for: ";
                        $title .= $this->getNotificationContent($record['content_type'], $record['object_id'])['title'];
                    } else {
                        return null;
                    }
                    break;
                }
            case 'reported': {
                    if ($opt['other']) {

                        if ($record['content_type'] === 'report') {
                            //user submitted a report: message
                            $title .=  " submitted a {$record['message']} report.";
                        } else {
                            //user reported content_type as: message
                            $title .=  " reported a {$record['content_type']} as {$record['message']}.";
                        }
                    } else {
                        return null;
                    }
                    break;
                }

            default: {
                    if ($opt['other']) {
                        $title .=  ': ' . $record['message'];
                    }
                    break;
                }
        }


        return [
            'uri' => $url,
            'title' => $title,
            'timestamp' => $record['date_added'],
            'author' =>  $record['actor_username'],
            'content' => $content,
            'categories' => ['notification', $record['action_type']],
            'uid' => $url
        ];
    }

    private function getComment($comment_id)
    {
        try {
            $d = $this->getData(self::URI . "/api/comments/{$comment_id}/?format=json", true, true);
        } catch (HttpException $e) {
            if ($e->getCode() === 404) {
                return '<i>Deleted comment.</i>';
            } else {
                returnServerError(var_dump($e));
            }
        }


        $content = '<b>' . $d['owner_displayname'] . ' said:</b><blockquote>' . nl2br($d['content']) . '</blockquote><br/>';

        if (!is_null($d['replying_to'])) {
            try {
                $dd = $this->getData(self::URI . "/api/comments/{$d['replying_to']}/?format=json", true, true);
                $content .= '<b>in reply to' . $dd['owner_displayname'] . ' :</b><blockquote>' . nl2br($dd['content']) . '</blockquote>';
            } catch (HttpException $e) {
                if ($e->getCode() === 404) {
                    $content .= '<i>Deleted comment.</i>';
                } else {
                    returnServerError(var_dump($e));
                }
            }
        }

        return $content;
    }

    private function getNotificationContent($type, $id, array $context = null)
    {
        $item = [];

        //if commented
        if (!is_null($context) && array_key_exists('action_type', $context) && $context['action_type'] === 'commented') {
            //if reply
            if ($context['content_type'] != 'comment') {
            } else {
                //if reply to submission, there is no comment id!
                //object_id or comment_host_id is NOT the comment id!
                $time = $context['date_added'];
                $submission_type = $context['content_type'];

                // api/galleries/images/460559/comments/?&page=1&page_size=30&child_page_size=100
                switch ($submission_type) {
                    case 'image':
                    case 'galleryimage':
                        return $this->getImage($id);
                    case 'post':
                        return $this->getPost($id);
                    case 'commission':
                        return $this->getCommission($id);
                    case 'joincommissionrequest':
                        return $this->getCommissionInbox($id);
                }
            }

            // $item['content'] = $this->getComment($context['object_id']) . '<hr/><br/>' . $item['content'];
        }

        switch ($type) {
            case 'image':
            case 'galleryimage':
                return $this->getImage($id);
            case 'post':
                return $this->getPost($id);
            case 'commission':
                return $this->getCommission($id);
            case 'joincommissionrequest':
                return $this->getCommissionInbox($id);
            case 'comment':
            case 'commented':
                $item = $this->getNotificationContent($context['comment_host_content_type'], $context['comment_host_id']);
                $item['content'] = $this->getComment($id) . '<hr/><br/>' . $item['content'];
                return $item;
        }
        return null;
    }

    private function resolveNotificationURL($comment, $type, $id)
    {
        $url = null;
        $api = null;
        $host = null;

        switch ($type) {
            case 'userprofile': {
                    if ($comment['action_type'] === 'opened_comms') {
                        $api = "/api/commissions/?&page_size=1&owner={$comment['owner']}&format=json";
                        $url = "/profile/{$comment['actor_username']}/commissions";
                    } else {
                        $api = "api/user_profiles/{$comment['actor_username']}/?format=json";
                        $url = "/profile/{$comment['actor_username']}";
                    }
                }
            case 'image':
            case 'galleryimage': {
                    $api = "/api/galleries/images/{$id}/?format=json";
                    $url = "/images/{$id}";
                    break;
                }
            case 'post': {
                    $api = "/api/posts/{$id}/?format=json";
                    $url = "/posts/{$id}";
                    break;
                }
            case 'tag': {
                    $api = "https://itaku.ee/api/tags/{$id}/?format=json";
                    $url = "/tags/{$id}";
                    break;
                }
            case 'joincommissionrequest': {
                    $api = "/api/commission_join_requests/{$id}/?format=json";
                    $url = "/commission-inbox/{$id}";
                    break;
                }
            case 'commission': {
                    $api = "/api/commissions/{$id}/?format=json";
                    $url = "/commissions/{$id}";
                    break;
                }
            case 'comment': {
                    $api = "/api/comments/{$id}/?format=json";
                    //returns link to comment in post/image/etc
                    $host = $this->resolveNotificationURL($comment, $comment['comment_host_content_type'], $comment['comment_host_id']);
                    $host['url'] .= "?comment={$id}";
                    break;
                }
        }

        if (!is_null($url)) {
            $url = self::URI . $url;
        }

        return [
            'id' => $id,
            'url' => $url,
            'api' => $api,
            'host' => $host
        ];
    }

    private function getCommissionInbox($id, array $metadata = null, bool $inclStatus = false)
    {
        $uri = self::URI . '/commission-inbox/' . $id;
        $url = self::URI . '/api/commission_join_requests/' . $id . '/?format=json';
        $data = $metadata ?? $this->getData($url, true, true)
            or returnServerError("Could not load $url");
        $title = '';
        if ($inclStatus) {
            $title = "{$data['status']} on: ";
        }
        $title .= "{$data['commission']['comm_type']} - {$data['commission']['title']}";

        return [
            'uri' => $uri,
            'title' => $title,
            'timestamp' => $data['date_added'],
            'author' =>  $data['owner_username'],
            'content' => $this->getCommission($data['commission']['id'], $data['commission'])['content'],
            'categories' => ['commission-inbox'],
            'uid' => $uri
        ];
    }
*/

    private function getMessage(array $record, int $owner_id)
    {
        $url = self::URI . "/api/messenger/messages/?chat={$record['id']}&page_size=30&page=1&format=json";
        $data = $this->getData($url, false, true);
        foreach ($data['results'] as $msg) {
            if ($msg['owner'] != $owner_id) {
                $this->addItem([
                    'uri' => 'https://itaku.ee/dms',
                    'title' => "New DM from @{$msg['owner_displayname']}",
                    'timestamp' => $msg['date_added'],
                    'author' =>  $msg['owner_username'],
                    'content' => nl2br($msg['content']),
                    'categories' => ['message'],
                    'uid' => 'message/' . $record['id'] . '/' . $msg['id']
                ]);
            }
        }
    }

    private function getSubmissionInbox(array $record)
    {
        switch ($record['content_type']) {
            case 'galleryimage':
                return $this->getImage($record['object_id'], $record['content_object']);
                break;
            case 'post':
                return $this->getPost($record['object_id'], $record['content_object']);
                break;
            case 'commission':
                return $this->getCommission($record['object_id'], $record['content_object']);
                break;
        }

        return null;
    }

    private function getTagSuggestions(array $record)
    {
        $content = "<p>For image: <b>{$record['image']['title']}</b></p><img src=\"{$record['image']['image_lg']}\"/><br/>";
        if (strlen($record['message']) > 0) {
            $content .= '<p><b>Message from the suggester:</b><blockquote>' . nl2br($record['message']) . '</blockquote><hr/></p>';
        } else {
            $content .= '<p>No message provided from the suggester.</p>' . '<hr/><p>';
        }

        if (!is_null($record['suggested_maturity_rating'])) {
            $prev_maturity = $record['image']['maturity_rating'];
            $content .= "Maturity: ❌{$prev_maturity} ➕{$record['suggested_maturity_rating']}<br/>";
        }

        if (
            count($record['tags_to_add']) > 0 ||
            count($record['tags_to_remove']) > 0
        ) {
            $tag_types = [
                'ARTIST' => '',
                'COPYRIGHT' => '',
                'CHARACTER' => '',
                'SPECIES' => '',
                'GENERAL' => '',
                'META' => ''
            ];

            if (count($record['tags_to_remove']) > 0) {
                foreach ($record['tags_to_remove'] as $tag) {
                    $url = self::URI . '/tags/' . $tag['id'];
                    $tag_types[$tag['tag_type']] .= "❌<a href=\"{$url}\">#{$tag['name']}</a> ";
                }
            }

            if (count($record['tags_to_add']) > 0) {
                foreach ($record['tags_to_add'] as $tag) {
                    $url = self::URI . '/tags/' . $tag['id'];
                    $tag_types[$tag['tag_type']] .= "➕<a href=\"{$url}\">#{$tag['name']}</a> ";
                }
            }

            foreach ($tag_types as $type => $str) {
                if (strlen($str) > 0) {
                    $content .= "🏷 <b>{$type}:</b> {$str}<br/>";
                }
            }
        }

        $content .= '</p><br/>Open this article to resolve suggestion. Alternatively, a tag moderator will resolve this suggestion on your behalf.';

        return [
            'uri' => self::URI . '/tag-suggestions',
            'title' => 'New suggestion for: ' . $record['image']['title'],
            'timestamp' => $record['date_added'],
            'author' =>  $record['owner_username'],
            'content' => $content,
            'categories' => ['inbox', 'tag suggestion'],
            'uid' => self::URI . '/tag-suggestions/' . $record['id']
        ];
    }

    private function getIncomingCommission(array $record)
    {
        $comm = $this->getCommission($record['commission']['id'], $record['commission']);
        $title = "{$record['status']} for: {$comm['title']}";
        $url = "https://itaku.ee/commission-inbox/{$record['id']}";
        $content = "<b>Request from {$record['owner_displayname']}:</b><blockquote>" . nl2br($record['description']) . '</blockquote><hr/>' . $comm['content'];
        return [
            'uri' => $url,
            'title' => $title,
            'timestamp' => $record['date_added'],
            'author' =>  $record['owner_username'],
            'content' => $content,
            'categories' => ['inbox', 'incoming commission request'],
            'uid' => $url
        ];
    }

    private function getFeed(array $opt)
    {
        $url = self::URI . "/api/feed/?date_range={$opt['range']}&ordering={$opt['order']}&page=1&page_size=30&format=json&visibility=PUBLIC&visibility=PROFILE_ONLY&by_following=true";

        if (!$opt['reshares']) {
            $url .= "&hide_reshares=true";
        }
        if ($opt['rating_s']) {
            $url .= "&maturity_rating=SFW";
        }
        if ($opt['rating_q']) {
            $url .= "&maturity_rating=Questionable";
        }
        if ($opt['rating_e']) {
            $url .= "&maturity_rating=NSFW";
        }

        return $this->getData($url, false, true);
    }

    private function getPost($id, array $metadata = null)
    {
        $uri = self::URI . '/posts/' . $id;
        $url = self::URI . '/api/posts/' . $id . '/?format=json';
        try {
            $data = $metadata ?? $this->getData($url, true, true)
                or returnServerError("Could not load $url");
        } catch (HttpException $e) {
            if ($e->getCode() === 404) {
                return [
                    'uri' => $uri,
                    'title' => "Deleted post",
                    'timestamp' => '@0',
                    'author' =>  'deleted',
                    'content' => 'Deleted post',
                    'categories' => ['post', 'deleted'],
                    'uid' => $uri
                ];
            } else {
                returnServerError(var_dump($e));
            }
        }

        $content_str = nl2br($data['content']);
        $content = "<p>{$content_str}</p><br/>"; //TODO: Add link and itaku user mention detection and convert into links.

        if (sizeof($data['tags']) > 0) {
            $content .= "🏷 Tag(s): ";
            foreach ($data['tags'] as $tag) {
                $url = self::URI . '/home/images?tags=' . $tag['name'];
                $content .= "<a href=\"{$url}\">#{$tag['name']}</a> ";
            }
            $content .= "<br/>";
        }

        if (sizeof($data['folders']) > 0) {
            $content .= "📁 In Folder(s): ";
            foreach ($data['folders'] as $folder) {
                $url = self::URI . '/profile/' . $data['owner_username'] . '/posts/' . $folder['id'];
                $content .= "<a href=\"{$url}\">#{$folder['title']}</a> ";
            }
        }

        $content .= "<hr/>";
        if (sizeof($data['gallery_images']) > 0) {
            foreach ($data['gallery_images'] as $media) {
                $title = $media['title'];
                $url = self::URI . '/images/' . $media['id'];
                $src = $media['image_xl'];
                $content .= "<p>";
                $content .= "<a href=\"{$url}\"><b>{$title}</b></a><br/>";
                if ($media['is_thumbnail_for_video']) {
                    $url = self::URI . '/api/galleries/images/' . $media['id'] . '/?format=json';
                    $media_data = $this->getData($url, true, true)
                        or returnServerError("Could not load $url");
                    $content .= "<video controls src=\"{$media_data['video']['video']}\" poster=\"{$media['image_xl']}\"/>";
                } else {
                    $content .= "<a href=\"{$url}\"><img src=\"{$src}\"></a>";
                }
                $content .= "</p><br/>";
            }
        }

        return [
            'uri' => $uri,
            'title' => $data['title'],
            'timestamp' => $data['date_added'],
            'author' =>  $data['owner_username'],
            'content' => $content,
            'categories' => ['post'],
            'uid' => $uri
        ];
    }

    private function getCommission($id, array $metadata = null)
    {
        $url = self::URI . '/api/commissions/' . $id . '/?format=json';
        $uri = self::URI . '/commissions/' . $id;

        try {
            $data = $metadata ?? $this->getData($url, true, true)
                or returnServerError("Could not load $url");
        } catch (HttpException $e) {
            if ($e->getCode() === 404) {
                return [
                    'uri' => $uri,
                    'title' => "Deleted commission",
                    'timestamp' => '@0',
                    'author' =>  'deleted',
                    'content' => 'Deleted commission',
                    'categories' => ['commission', 'deleted'],
                    'uid' => $uri
                ];
            } else {
                returnServerError(var_dump($e));
            }
        }

        $content_str = nl2br($data['description']);
        $content = "<p>{$content_str}</p><br>"; //TODO: Add link and itaku user mention detection and convert into links.

        if (array_key_exists('tags', $data) && sizeof($data['tags']) > 0) {
            $content .= "🏷 Tag(s): ";
            foreach ($data['tags'] as $tag) {
                $url = self::URI . '/home/images?tags=' . $tag['name'];
                $content .= "<a href=\"{$url}\">#{$tag['name']}</a> ";
            }
            $content .= "<br/>";
        }

        if (array_key_exists('reference_gallery_sections', $data) && sizeof($data['reference_gallery_sections']) > 0) {
            $content .= "📁 Example folder(s): ";
            foreach ($data['folders'] as $folder) {
                $url = self::URI . '/profile/' . $data['owner_username'] . '/gallery/' . $folder['id'];
                $folder_name = $folder['title'];
                if (!is_null($folder['group'])) {
                    $folder_name = $folder['group']['title'] . '/' . $folder_name;
                }
                $content .= "<a href=\"{$url}\">#{$folder_name}</a> ";
            }
        }

        $content .= "<hr/>";
        if (!is_null($data['thumbnail_detail'])) {
            $content .= "<p>";
            $content .= "<a href=\"{$uri}\"><b>{$data['thumbnail_detail']['title']}</b></a><br/>";
            if ($data['thumbnail_detail']['is_thumbnail_for_video']) {
                $url = self::URI . '/api/galleries/images/' . $data['thumbnail_detail']['id'] . '/?format=json';
                $media_data = $this->getData($url, true, true)
                    or returnServerError("Could not load $url");
                $content .= "<video controls src=\"{$media_data['video']['video']}\" poster=\"{$data['thumbnail_detail']['image_lg']}\"/>";
            } else {
                $content .= "<a href=\"{$uri}\"><img src=\"{$data['thumbnail_detail']['image_lg']}\"></a>";
            }

            $content .= "</p>";
        }

        return [
            'uri' => $uri,
            'title' => "{$data['comm_type']} - {$data['title']}",
            'timestamp' => $data['date_added'],
            'author' =>  $data['owner_username'],
            'content' => $content,
            'categories' => ['commission', $data['comm_type']],
            'uid' => $uri
        ];
    }

    private function getImage($id /* array $metadata = null */) //$metadata disabled due to no essential information available in ./api/feed/ or ./api/galleries/images/ results.
    {
        $uri = self::URI . '/images/' . $id;
        $url = self::URI . '/api/galleries/images/' . $id . '/?format=json';
        try {
            $data = /* $metadata ?? */ $this->getData($url, true, true)
                or returnServerError("Could not load $url");
        } catch (HttpException $e) {
            if ($e->getCode() === 404) {
                return [
                    'uri' => $uri,
                    'title' => "Deleted Image",
                    'timestamp' => '@0',
                    'author' =>  'deleted',
                    'content' => 'Deleted image',
                    'categories' => ['image', 'deleted'],
                    'uid' => $uri
                ];
            } else {
                returnServerError(var_dump($e));
            }
        }

        $content_str = nl2br($data['description']);
        $content = "<p>{$content_str}</p><br/>"; //TODO: Add link and itaku user mention detection and convert into links.

        if (array_key_exists('tags', $data) && sizeof($data['tags']) > 0) {
            $tag_types = [
                'ARTIST' => '',
                'COPYRIGHT' => '',
                'CHARACTER' => '',
                'SPECIES' => '',
                'GENERAL' => '',
                'META' => ''
            ];
            foreach ($data['tags'] as $tag) {
                $url = self::URI . '/home/images?tags=' . $tag['name'];
                $str = "<a href=\"{$url}\">#{$tag['name']}</a> ";
                $tag_types[$tag['tag_type']] .= $str;
            }

            foreach ($tag_types as $type => $str) {
                if (strlen($str) > 0) {
                    $content .= "🏷 <b>{$type}:</b>: {$str}<br/>";
                }
            }
        }

        if (array_key_exists('sections', $data) && sizeof($data['sections']) > 0) {
            $content .= "📁 In Folder(s): ";
            foreach ($data['sections'] as $folder) {
                $url = self::URI . '/profile/' . $data['owner_username'] . '/gallery/' . $folder['id'];
                $folder_name = $folder['title'];
                if (!is_null($folder['group'])) {
                    $folder_name = $folder['group']['title'] . '/' . $folder_name;
                }
                $content .= "<a href=\"{$url}\">#{$folder_name}</a> ";
            }
        }

        $content .= "<hr/>";

        if (array_key_exists('is_thumbnail_for_video', $data)) {
            $url = self::URI . '/api/galleries/images/' . $data['id'] . '/?format=json';
            $media_data = $this->getData($url, true, true)
                or returnServerError("Could not load $url");
            $content .= "<video controls src=\"{$media_data['video']['video']}\" poster=\"{$data['image_xl']}\"/>";
        } else {
            if (array_key_exists('video', $data) && is_null($data['video'])) {
                $content .= "<a href=\"{$uri}\"><img src=\"{$data['image_xl']}\"></a>";
            } else {
                $content .= "<video controls src=\"{$data['video']['video']}\" poster=\"{$data['image_xl']}\"/>";
            }
        }

        return [
            'uri' => $uri,
            'title' => $data['title'],
            'timestamp' => $data['date_added'],
            'author' =>  $data['owner_username'],
            'content' => $content,
            'categories' => ['image'],
            'uid' => $uri
        ];
    }

    private function getData(string $url, bool $cache = false, bool $getJSON = false, array $httpHeaders = [], array $curlOptions = [])
    {
        $httpHeaders[] = 'Authorization: Token ' . $this->token;
        if ($getJSON) { //get JSON object
            if ($cache) {
                $data = $this->loadCacheValue($url, 86400); // 24 hours
                if (is_null($data)) {
                    $data = getContents($url, $httpHeaders, $curlOptions) or returnServerError("Could not load $url");
                    $this->saveCacheValue($url, $data);
                }
            } else {
                $data = getContents($url, $httpHeaders, $curlOptions) or returnServerError("Could not load $url");
            }
            return json_decode($data, true);
        } else { //get simpleHTMLDOM object
            if ($cache) {
                $html = getSimpleHTMLDOMCached($url, 86400); // 24 hours
            } else {
                $html = getSimpleHTMLDOM($url);
            }
            $html = defaultLinkTo($html, $url);
            return $html;
        }
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
        $user_profile = $this->loadCacheValue('userprofile');
        if (isset($user_profile)) {
            return self::NAME . ' for ' . $user_profile['profile']['owner_username'];
        } else {
            return self::NAME;
        }
    }

    public function getURI()
    {
        $user_profile = $this->loadCacheValue('userprofile');
        if (isset($username)) {
            return self::URI . '/user/' . $user_profile['profile']['owner_username'];
        } else {
            return self::URI;
        }
    }
}
