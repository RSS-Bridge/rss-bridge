<?php

class Vk2Bridge extends BridgeAbstract
{
    const MAINTAINER = 'em92';
    const NAME = 'ВКонтакте';
    const URI = 'https://vk.com';
    const DESCRIPTION = 'Выводит записи на стене';
    const CACHE_TIMEOUT = 300; // 5 minutes
    const PARAMETERS = [
        [
            'u' => [
                'name' => 'Короткое имя группы или профиля (из ссылки)',
                'exampleValue' => 'goblin_oper_ru',
                'required' => true
            ],
            'hide_reposts' => [
                'name' => 'Скрыть репосты',
                'type' => 'checkbox',
            ]
        ]
    ];

    const CONFIGURATION = [
        'access_token' => [
            'required' => true,
        ],
    ];

    const TEST_DETECT_PARAMETERS = [
        'https://vk.com/id1' => ['u' => 'id1'],
        'https://vk.com/groupname' => ['u' => 'groupname'],
        'https://m.vk.com/groupname' => ['u' => 'groupname'],
        'https://vk.com/groupname/anythingelse' => ['u' => 'groupname'],
        'https://vk.com/groupname?w=somethingelse' => ['u' => 'groupname'],
        'https://vk.com/with_underscore' => ['u' => 'with_underscore'],
        'https://vk.com/vk.cats' => ['u' => 'vk.cats'],
    ];

    protected $ownerNames = [];
    protected $pageName;
    private $urlRegex = '/vk\.com\/([\w.]+)/';
    private $rateLimitCacheKey = 'vk2_rate_limit';

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return urljoin(static::URI, urlencode($this->getInput('u')));
        }

        return parent::getURI();
    }

    public function getName()
    {
        if ($this->pageName) {
            return $this->pageName;
        }

        return parent::getName();
    }

    public function detectParameters($url)
    {
        if (preg_match($this->urlRegex, $url, $matches)) {
            return ['u' => $matches[1]];
        }

        return null;
    }

    protected function getPostURI($post)
    {
        $r = 'https://vk.com/wall' . $post['owner_id'] . '_';
        if (isset($post['reply_post_id'])) {
            $r .= $post['reply_post_id'] . '?reply=' . $post['id'] . '&thread=' . $post['parents_stack'][0];
        } else {
            $r .= $post['id'];
        }
        return $r;
    }

    // This function is based on SlackCoyote's vkfeed2rss
    // https://github.com/em92/vkfeed2rss
    protected function generateContentFromPost($post)
    {
        // it's what we will return
        $ret = $post['text'];

        // html special characters convertion
        $ret = htmlentities($ret, ENT_QUOTES | ENT_HTML401);
        // change all linebreak to HTML compatible <br />
        $ret = nl2br($ret);

        $ret = "<p>$ret</p>";

        // find URLs
        $ret = preg_replace(
            '/((https?|ftp|gopher)\:\/\/[a-zA-Z0-9\-\.]+(:[a-zA-Z0-9]*)?\/?([@\w\-\+\.\?\,\'\/&amp;%\$#\=~\x5C])*)/',
            "<a href='$1'>$1</a>",
            $ret
        );

        // find [id1|Pawel Durow] form links
        $ret = preg_replace('/\[(\w+)\|([^\]]+)\]/', "<a href='https://vk.com/$1'>$2</a>", $ret);


        // attachments
        if (isset($post['attachments'])) {
            // level 1
            foreach ($post['attachments'] as $attachment) {
                if ($attachment['type'] == 'video') {
                    // VK videos
                    $title = e($attachment['video']['title']);
                    $photo = e($this->getImageURLWithLargestWidth($attachment['video']['image']));
                    $href = "https://vk.com/video{$attachment['video']['owner_id']}_{$attachment['video']['id']}";
                    $ret .= "<p><a href='{$href}'><img src='{$photo}' alt='Video: {$title}'><br/>Video: {$title}</a></p>";
                } elseif ($attachment['type'] == 'audio') {
                    // VK audio
                    $artist = e($attachment['audio']['artist']);
                    $title = e($attachment['audio']['title']);
                    $ret .= "<p>Audio: {$artist} - {$title}</p>";
                } elseif ($attachment['type'] == 'doc' and $attachment['doc']['ext'] != 'gif') {
                    // any doc apart of gif
                    $doc_url = e($attachment['doc']['url']);
                    $title = e($attachment['doc']['title']);
                    $ret .= "<p><a href='{$doc_url}'>Документ: {$title}</a></p>";
                }
            }
            // level 2
            foreach ($post['attachments'] as $attachment) {
                if ($attachment['type'] == 'photo') {
                    // JPEG, PNG photos
                    // GIF in vk is a document, so, not handled as photo
                    $photo = e($this->getImageURLWithLargestWidth($attachment['photo']['sizes']));
                    $text = e($attachment['photo']['text']);
                    $ret .= "<p><img src='{$photo}' alt='{$text}'></p>";
                } elseif ($attachment['type'] == 'doc' and $attachment['doc']['ext'] == 'gif') {
                    // GIF docs
                    $url = e($attachment['doc']['url']);
                    $ret .= "<p><img src='{$url}'></p>";
                } elseif ($attachment['type'] == 'link') {
                    // links
                    $url = e($attachment['link']['url']);
                    $url = str_replace('https://m.vk.com', 'https://vk.com', $url);
                    $title = e($attachment['link']['title']);
                    if (isset($attachment['link']['photo'])) {
                        $photo = $this->getImageURLWithLargestWidth($attachment['link']['photo']['sizes']);
                        $ret .= "<p><a href='{$url}'><img src='{$photo}' alt='{$title}'><br>{$title}</a></p>";
                    } else {
                        $ret .= "<p><a href='{$url}'>{$title}</a></p>";
                    }
                } elseif ($attachment['type'] == 'note') {
                    // notes
                    $title = e($attachment['note']['title']);
                    $url = e($attachment['note']['view_url']);
                    $ret .= "<p><a href='{$url}'>{$title}</a></p>";
                } elseif ($attachment['type'] == 'poll') {
                    // polls
                    $question = e($attachment['poll']['question']);
                    $vote_count = $attachment['poll']['votes'];
                    $answers = $attachment['poll']['answers'];
                    $ret .= "<p>Poll: {$question} ({$vote_count} votes)<br />";
                    foreach ($answers as $answer) {
                        $text = e($answer['text']);
                        $votes = $answer['votes'];
                        $rate = $answer['rate'];
                        $ret .= "* {$text}: {$votes} ({$rate}%)<br />";
                    }
                    $ret .= '</p>';
                } elseif ($attachment['type'] == 'album') {
                    $album = $attachment['album'];
                    $url = "https://vk.com/album{$album['owner_id']}_{$album['id']}";
                    $title = 'Альбом: ' . $album['title'];
                    $photo = $this->getImageURLWithLargestWidth($album['thumb']['sizes']);
                    $ret .= "<p><a href='{$url}'><img src='{$photo}' alt='{$title}'><br>{$title}</a></p>";
                } elseif (!in_array($attachment['type'], ['video', 'audio', 'doc'])) {
                    $ret .= "<p>Unknown attachment type: {$attachment['type']}</p>";
                }
            }
        }

        return $ret;
    }

    protected function getImageURLWithLargestWidth($items)
    {
        usort($items, function ($a, $b) {
            return $b['width'] - $a['width'];
        });
        return $items[0]['url'];
    }

    public function collectData()
    {
        if ($this->cache->get($this->rateLimitCacheKey)) {
            throw new RateLimitException();
        }

        $u = $this->getInput('u');
        $ownerId = null;

        // getting ownerId from url
        $r = preg_match('/^(club|public)(\d+)$/', $u, $matches);
        if ($r) {
            $ownerId = -intval($matches[2]);
        } else {
            $r = preg_match('/^(id)(\d+)$/', $u, $matches);
            if ($r) {
                $ownerId = intval($matches[2]);
            }
        }

        // getting owner id from API
        if (is_null($ownerId)) {
            $r = $this->api('groups.getById', [
                'group_ids' => $u,
            ], [100]);
            if (isset($r['response'][0])) {
                $ownerId = -$r['response'][0]['id'];
            } else {
                $r = $this->api('users.get', [
                    'user_ids' => $u,
                ]);
                if (count($r['response']) > 0) {
                    $ownerId = $r['response'][0]['id'];
                }
            }
        }

        if (is_null($ownerId)) {
            returnServerError('Could not detect owner id');
        }

        $r = $this->api('wall.get', [
            'owner_id' => $ownerId,
            'extended' => '1',
        ]);

        // preparing ownerNames dictionary
        foreach ($r['response']['profiles'] as $profile) {
            $this->ownerNames[$profile['id']] = $profile['first_name'] . ' ' . $profile['last_name'];
        }
        foreach ($r['response']['groups'] as $group) {
            $this->ownerNames[-$group['id']] = $group['name'];
        }
        $this->generateFeed($r);
    }

    protected function generateFeed($r)
    {
        $ownerId = 0;

        foreach ($r['response']['items'] as $post) {
            if (!$ownerId) {
                $ownerId = $post['owner_id'];
            }
            $item = new FeedItem();
            $content = $this->generateContentFromPost($post);
            if (isset($post['copy_history'])) {
                if ($this->getInput('hide_reposts')) {
                    continue;
                }
                $originalPost = $post['copy_history'][0];
                if ($originalPost['from_id'] < 0) {
                    $originalPostAuthorScreenName = 'club' . (-$originalPost['owner_id']);
                } else {
                    $originalPostAuthorScreenName = 'id' . $originalPost['owner_id'];
                }
                $originalPostAuthorURI = 'https://vk.com/' . $originalPostAuthorScreenName;
                $originalPostAuthorName = $this->ownerNames[$originalPost['from_id']];
                $originalPostAuthor = "<a href='$originalPostAuthorURI'>$originalPostAuthorName</a>";
                $content .= '<p>Репост (<a href="';
                $content .= $this->getPostURI($originalPost);
                $content .= '">Пост</a> от ';
                $content .= $originalPostAuthor;
                $content .= '):</p>';
                $content .= $this->generateContentFromPost($originalPost);
            }
            $item->setContent($content);
            $item->setTimestamp($post['date']);
            $item->setAuthor($this->ownerNames[$post['from_id']]);
            $item->setTitle($this->getTitle(strip_tags($content)));
            $item->setURI($this->getPostURI($post));

            $this->items[] = $item;
        }

        $this->pageName = $this->ownerNames[$ownerId];
    }

    protected function getTitle($content)
    {
        $content = explode('<br>', $content)[0];
        $content = strip_tags($content);
        preg_match('/^[:\,"\w\ \p{L}\(\)\?#«»\-\–\—||&\.%\\₽\/+\;\!]+/mu', htmlspecialchars_decode($content), $result);
        if (count($result) == 0) {
            return 'untitled';
        }
        return $result[0];
    }

    protected function api($method, array $params, $expected_error_codes = [])
    {
        $access_token = $this->getOption('access_token');
        if (!$access_token) {
            returnServerError('You cannot run VK API methods without access_token');
        }
        $params['v'] = '5.131';
        $r = json_decode(
            getContents(
                'https://api.vk.com/method/' . $method . '?' . http_build_query($params),
                ['Authorization: Bearer ' . $access_token]
            ),
            true
        );
        if (isset($r['error']) && !in_array($r['error']['error_code'], $expected_error_codes)) {
            if ($r['error']['error_code'] == 6) {
                $this->cache->set($this->rateLimitCacheKey, true, 5);
            } else if ($r['error']['error_code'] == 29) {
                // wall.get has limit of 5000 requests per day
                // if that limit is hit, VK returns error 29
                $this->cache->set($this->rateLimitCacheKey, true, 60 * 30);
            }
            returnServerError('API returned error: ' . $r['error']['error_msg'] . ' (' . $r['error']['error_code'] . ')');
        }
        return $r;
    }
}
