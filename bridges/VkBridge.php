<?php

class VkBridge extends BridgeAbstract
{
    const MAINTAINER = 'em92';
    // const MAINTAINER = 'pmaziere';
    // const MAINTAINER = 'ahiles3005';
    const NAME = 'VK.com';
    const URI = 'https://vk.com/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION = 'Working with open pages';
    const PARAMETERS = [
        [
            'u' => [
                'name' => 'Group or user name',
                'exampleValue' => 'elonmusk_tech',
                'required' => true
            ],
            'hide_reposts' => [
                'name' => 'Hide reposts',
                'type' => 'checkbox',
            ]
        ]
    ];

    const CONFIGURATION = [
        'access_token' => [
            'required' => false,
        ],
    ];

    protected $videos = [];
    protected $ownerNames = [];
    protected $pageName;
    protected $usingRealVKAPI = false;

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return static::URI . urlencode($this->getInput('u'));
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

        if ($this->usingRealVKAPI) {
            // html special characters convertion
            $ret = htmlentities($ret, ENT_QUOTES | ENT_HTML401);
            // change all linebreak to HTML compatible <br />
            $ret = nl2br($ret);
            // find [id1|Pawel Durow] form links
            $ret = preg_replace('/\[(\w+)\|([^\]]+)\]/', "<a href='https://vk.com/$1'>$2</a>", $ret);

            $ret = "<p>$ret</p>";
        }

        // find URLs
        $ret = preg_replace(
            '/((https?|ftp|gopher)\:\/\/[a-zA-Z0-9\-\.]+(:[a-zA-Z0-9]*)?\/?([@\w\-\+\.\?\,\'\/&amp;%\$#\=~\x5C])*)/',
            "<a href='$1'>$1</a>",
            $ret
        );

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
                    $ret .= "<p><a href='{$doc_url}'>Document: {$title}</a></p>";
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
                } elseif (!in_array($attachment['type'], ['video', 'audio', 'doc'])) {
                    $ret .= "<p>Unknown attachment type: {$attachment['type']}</p>";
                }
            }
        }

        return $ret;
    }

    protected function getImageURLWithLargestWidth($items) {
        usort($items, function($a, $b) {
            return $b['width'] - $a['width'];
        });
        return $items[0]['url'];
    }

    public function collectData()
    {
        if ($this->getOption('access_token')) {
            $r = $this->api('wall.get', [
                'domain' => $this->getInput('u'),
                'extended' => '1',
            ]);

            // TODO: ownernames to local variable

            // preparing ownerNames dictionary
            foreach ($r['response']['profiles'] as $profile) {
                $this->ownerNames[$profile['id']] = $profile['first_name'] . ' ' . $profile['last_name'];
            }
            foreach ($r['response']['groups'] as $group) {
                $this->ownerNames[-$group['id']] = $group['name'];
            }
        } else {
            $r = $this->fakeWallGet();
        }
        $this->generateFeed($r);
    }

    protected function generateFeed($r) {
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
                $content .= '<p>Reposted (<a href="';
                $content .= $this->getPostURI($originalPost);
                $content .= '">Post</a> from ';
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

    protected function generateWallPostJSON($post, $is_repost = false) {
        $item = [];
        if ($is_repost) {
            $author = $post->find('.copy_post_author .copy_author', 0);
            $from_id = explode("_", $author->getAttribute('data-post-id'))[0];
        } else {
            $author = $post->find('.post_author .author', 0);
            $from_id = $author->getAttribute('data-from-id');
        }

        $post_id = $author->getAttribute('data-post-id');

        $this->ownerNames[$from_id] = $author->innertext;
        $item['from_id'] = intval($from_id);

        $tmp = explode('_', $post_id);
        $item['owner_id'] = intval($tmp[0]);
        $item['id'] = intval($tmp[1]);

        if (strpos($post->getAttribute('class'), 'post_fixed') !== false) {
            $item['is_pinned'] = 1;
        }

        if ($is_repost) {
            $item['date'] = $this->getTime($post, 'a.published_by_date');
        } else {
            $item['date'] = $this->getTime($post, 'span.rel_date');
        }

        $item['text'] = '';
        $attachments = [];
        $previous_image = '';
        if ($is_repost) {
            $wpc_elements = $post->children();
        } else {
            $wpc_elements = $post->find('.wall_post_cont', 0)->children();
        }
        foreach($wpc_elements as $wpc) {
            // wpc = wall post content

            $wpc_classes = explode(' ', $wpc->getAttribute('class'));

            if (in_array('wall_post_text', $wpc_classes)) {
                $item['text'] = "<p>{$wpc->innertext}</p>";
            } else if (in_array('page_post_sized_thumbs', $wpc_classes)) {
                preg_match('/background-image: url\((.*)\)/', $wpc->find('a', 0), $matches);
                if (count($matches) > 0) {
                    $previous_image .= $matches[1];
                }
            } else if (in_array('post_video_desc', $wpc_classes)) {
                $a = $wpc->find('a', 0);
                $video_id = $a->getAttribute('data-video');
                $tmp = explode('_', $video_id);
                $attachments[] = [
                    'type' => 'video',
                    'video' => [
                        'title' => $a->find('.post_video_title', 0)->innertext,
                        'owner_id' => intval($tmp[0]),
                        'id' => intval($tmp[1]),
                        'image' => [[
                            'width' => null,
                            'url' => $previous_image,
                        ]],
                    ],
                ];
                $previous_image = '';
            } else if (in_array('media_link', $wpc_classes)) {
                $photo = $wpc->find('.media_link__photo', 0);
                $attachments[] = [
                    'type' => 'link',
                    'link' => [
                        'title' => $photo->getAttribute('alt'),
                        'url' => $photo->parent()->getAttribute('href'),
                        'photo' => [
                            'sizes' => [[
                                'width' => null,
                                'url' => $photo->getAttribute('src'),
                            ]],
                        ]
                    ]
                ];
            } else if (in_array('media_desc--link', $wpc_classes)) {
                $attachments[] = [
                    'type' => 'link',
                    'link' => [
                        'title' => $wpc->find('.mail_link__title', 0)->innertext,
                        'url' => $wpc->find('.mail_link', 0)->getAttribute('href'),
                    ],
                ];
            }
        }

        $repost = $post->find('.copy_quote', 0);
        if ($repost) {
            $item['copy_history'] = [$this->generateWallPostJSON($repost, true)];
        }

        if (count($attachments)) {
            $item['attachments'] = $attachments;
        }

        return $item;
    }

    protected function fakeWallGet()
    {
        $text_html = $this->getContents();

        $text_html = iconv('windows-1251', 'utf-8//ignore', $text_html);
        // makes album link generating work correctly
        // TODO: do we need this?
        $text_html = str_replace('"class="page_album_link">', '" class="page_album_link">', $text_html);
        $html = str_get_html($text_html);
        $pageName = $html->find('.page_name', 0);
        if (is_object($pageName)) {
            $pageName = $pageName->plaintext;
            $this->pageName = htmlspecialchars_decode($pageName);
        }
        // TODO: do we need this?
        foreach ($html->find('div.replies') as $comment_block) {
            $comment_block->outertext = '';
        }
        $html->load($html->save());

        $pinned_post_item = null;
        $last_post_id = 0;
        $response_items = [];

        foreach ($html->find('.post') as $post) {
            if ($post->find('.wall_post_text_deleted')) {
                // repost of deleted post
                continue;
            }

            defaultLinkTo($post, self::URI);

            // fix links and get post hashtags
            // TODO: need the way to store hashtags to set category
            $hashtags = [];
            foreach ($post->find('a') as $a) {
                $href = $a->getAttribute('href');
                $innertext = $a->innertext;

                $hashtag_prefix = '/feed?section=search&q=%23';
                $hashtag = null;

                if ($href && substr($href, 0, strlen($hashtag_prefix)) === $hashtag_prefix) {
                    $hashtag = urldecode(substr($href, strlen($hashtag_prefix)));
                } elseif (substr($innertext, 0, 1) == '#') {
                    $hashtag = $innertext;
                }

                if ($hashtag) {
                    $a->outertext = $innertext;
                    $hashtags[] = $hashtag;
                    continue;
                }

                $parsed_url = parse_url($href);

                if (array_key_exists('path', $parsed_url) === false) {
                    continue;
                }

                if (strpos($parsed_url['path'], '/away.php') === 0) {
                    parse_str($parsed_url['query'], $parsed_query);
                    $a->setAttribute('href', iconv(
                        'windows-1251',
                        'utf-8//ignore',
                        $parsed_query['to']
                    ));
                }
            }


            $response_items[] = $this->generateWallPostJSON($post);
        }

        return [
            'response' => [
                'items' => $response_items
            ],
        ];
    }

    protected function collectDataWithoutAPI()
    {
        $text_html = $this->getContents();

        $text_html = iconv('windows-1251', 'utf-8//ignore', $text_html);
        // makes album link generating work correctly
        $text_html = str_replace('"class="page_album_link">', '" class="page_album_link">', $text_html);
        $html = str_get_html($text_html);
        $pageName = $html->find('.page_name', 0);
        if (is_object($pageName)) {
            $pageName = $pageName->plaintext;
            $this->pageName = htmlspecialchars_decode($pageName);
        }
        foreach ($html->find('div.replies') as $comment_block) {
            $comment_block->outertext = '';
        }
        $html->load($html->save());

        $pinned_post_item = null;
        $last_post_id = 0;

        foreach ($html->find('.post') as $post) {
            if ($post->find('.wall_post_text_deleted')) {
                // repost of deleted post
                continue;
            }

            defaultLinkTo($post, self::URI);

            $post_videos = [];

            $is_pinned_post = false;
            if (strpos($post->getAttribute('class'), 'post_fixed') !== false) {
                $is_pinned_post = true;
            }

            if (is_object($post->find('a.wall_post_more', 0))) {
                //delete link "show full" in content
                $post->find('a.wall_post_more', 0)->outertext = '';
            }

            $content_suffix = '';

            // looking for external links
            $external_link_selectors = [
                'a.page_media_link_title',
                'div.page_media_link_title > a',
                'div.media_desc > a.lnk',
            ];

            foreach ($external_link_selectors as $sel) {
                if (is_object($post->find($sel, 0))) {
                    $a = $post->find($sel, 0);
                    $innertext = $a->innertext;
                    $parsed_url = parse_url($a->getAttribute('href'));
                    if (strpos($parsed_url['path'], '/away.php') !== 0) {
                        continue;
                    }
                    parse_str($parsed_url['query'], $parsed_query);
                    $content_suffix .= "<br>External link: <a href='" . $parsed_query['to'] . "'>$innertext</a>";
                }
            }

            // remove external link from content
            $external_link_selectors_to_remove = [
                'div.page_media_thumbed_link',
                'div.page_media_link_desc_wrap',
                'div.media_desc > a.lnk',
            ];

            foreach ($external_link_selectors_to_remove as $sel) {
                if (is_object($post->find($sel, 0))) {
                    $post->find($sel, 0)->outertext = '';
                }
            }

            // looking for article
            $article = $post->find('a.article_snippet', 0);
            if (is_object($article)) {
                if (strpos($article->getAttribute('class'), 'article_snippet_mini') !== false) {
                    $article_title_selector = 'div.article_snippet_mini_title';
                    $article_author_selector = 'div.article_snippet_mini_info > .mem_link,
						div.article_snippet_mini_info > .group_link';
                    $article_thumb_selector = 'div.article_snippet_mini_thumb';
                } else {
                    $article_title_selector = 'div.article_snippet__title';
                    $article_author_selector = 'div.article_snippet__author';
                    $article_thumb_selector = 'div.article_snippet__image';
                }
                $article_title = $article->find($article_title_selector, 0)->innertext;
                $article_author = $article->find($article_author_selector, 0)->innertext;
                $article_link = $article->getAttribute('href');
                $article_img_element_style = $article->find($article_thumb_selector, 0)->getAttribute('style');
                preg_match('/background-image: url\((.*)\)/', $article_img_element_style, $matches);
                if (count($matches) > 0) {
                    $content_suffix .= "<br><img src='" . $matches[1] . "'>";
                }
                $content_suffix .= "<br>Article: <a href='$article_link'>$article_title ($article_author)</a>";
                $article->outertext = '';
            }

            // get video on post
            $video = $post->find('div.post_video_desc', 0);
            $main_video_link = '';
            if (is_object($video)) {
                $video_title = $video->find('div.post_video_title', 0)->plaintext;
                $video_link = $video->find('a.lnk', 0)->getAttribute('href');
                $this->appendVideo($video_title, $video_link, $content_suffix, $post_videos);
                $video->outertext = '';
                $main_video_link = $video_link;
            }

            // get all other videos
            foreach ($post->find('a.page_post_thumb_video') as $a) {
                $video_title = htmlspecialchars_decode($a->getAttribute('aria-label'));
                $video_link = $a->getAttribute('href');
                if ($video_link != $main_video_link) {
                    $this->appendVideo($video_title, $video_link, $content_suffix, $post_videos);
                }
                $a->outertext = '';
            }

            // get all photos
            foreach ($post->find('div.wall_text a.page_post_thumb_wrap') as $a) {
                $result = $this->getPhoto($a);
                if ($result == null) {
                    continue;
                }
                $a->outertext = '';
                $content_suffix .= "<br>$result";
            }

            // get albums
            foreach ($post->find('.page_album_wrap') as $el) {
                $a = $el->find('.page_album_link', 0);
                $album_title = $a->find('.page_album_title_text', 0)->getAttribute('title');
                $album_link = $a->getAttribute('href');
                $el->outertext = '';
                $content_suffix .= "<br>Album: <a href='$album_link'>$album_title</a>";
            }

            // get photo documents
            foreach ($post->find('a.page_doc_photo_href') as $a) {
                $doc_link = $a->getAttribute('href');
                $doc_gif_label_element = $a->find('.page_gif_label', 0);
                $doc_title_element = $a->find('.doc_label', 0);

                if (is_object($doc_gif_label_element)) {
                    $gif_preview_img = backgroundToImg($a->find('.page_doc_photo', 0));
                    $content_suffix .= "<br>Gif: <a href='$doc_link'>$gif_preview_img</a>";
                } elseif (is_object($doc_title_element)) {
                    $doc_title = $doc_title_element->innertext;
                    $content_suffix .= "<br>Doc: <a href='$doc_link'>$doc_title</a>";
                } else {
                    continue;
                }

                $a->outertext = '';
            }

            // get other documents
            foreach ($post->find('div.page_doc_row') as $div) {
                $doc_title_element = $div->find('a.page_doc_title', 0);

                if (is_object($doc_title_element)) {
                    $doc_title = $doc_title_element->innertext;
                    $doc_link = $doc_title_element->getAttribute('href');
                    $content_suffix .= "<br>Doc: <a href='$doc_link'>$doc_title</a>";
                } else {
                    continue;
                }

                $div->outertext = '';
            }

            // get polls
            foreach ($post->find('div.page_media_poll_wrap') as $div) {
                $poll_title = $div->find('.page_media_poll_title', 0)->innertext;
                $content_suffix .= "<br>Poll: $poll_title";
                foreach ($div->find('div.page_poll_text') as $poll_stat_title) {
                    $content_suffix .= '<br>- ' . $poll_stat_title->innertext;
                }
                $div->outertext = '';
            }

            // get sign / post author
            $post_author = $pageName;
            $author_selectors = ['a.wall_signed_by', 'a.author'];
            foreach ($author_selectors as $author_selector) {
                $a = $post->find($author_selector, 0);
                if (is_object($a)) {
                    $post_author = $a->innertext;
                    $a->outertext = '';
                    break;
                }
            }

            // fix links and get post hashtags
            $hashtags = [];
            foreach ($post->find('a') as $a) {
                $href = $a->getAttribute('href');
                $innertext = $a->innertext;

                $hashtag_prefix = '/feed?section=search&q=%23';
                $hashtag = null;

                if ($href && substr($href, 0, strlen($hashtag_prefix)) === $hashtag_prefix) {
                    $hashtag = urldecode(substr($href, strlen($hashtag_prefix)));
                } elseif (substr($innertext, 0, 1) == '#') {
                    $hashtag = $innertext;
                }

                if ($hashtag) {
                    $a->outertext = $innertext;
                    $hashtags[] = $hashtag;
                    continue;
                }

                $parsed_url = parse_url($href);

                if (array_key_exists('path', $parsed_url) === false) {
                    continue;
                }

                if (strpos($parsed_url['path'], '/away.php') === 0) {
                    parse_str($parsed_url['query'], $parsed_query);
                    $a->setAttribute('href', iconv(
                        'windows-1251',
                        'utf-8//ignore',
                        $parsed_query['to']
                    ));
                }
            }

            $copy_quote = $post->find('div.copy_quote', 0);
            if (is_object($copy_quote)) {
                if ($this->getInput('hide_reposts') === true) {
                    continue;
                }
                if ($copy_post_header = $copy_quote->find('div.copy_post_header', 0)) {
                    $copy_post_header->outertext = '';
                }

                $second_copy_quote = $copy_quote->find('div.published_sec_quote', 0);
                if (is_object($second_copy_quote)) {
                    $second_copy_quote_author = $second_copy_quote->find('a.copy_author', 0)->outertext;
                    $second_copy_quote_content = $second_copy_quote->find('div.copy_post_date', 0)->outertext;
                    $second_copy_quote->outertext = "<br>Reposted ($second_copy_quote_author): $second_copy_quote_content";
                }
                $copy_quote_author = $copy_quote->find('a.copy_author', 0)->outertext;
                $copy_quote_content = $copy_quote->innertext;
                $copy_quote->outertext = "<br>Reposted ($copy_quote_author): <br>$copy_quote_content";
            }

            $item = [];
            $item['content'] = strip_tags(backgroundToImg($post->find('div.wall_text', 0)->innertext), '<a><br><img>');
            $item['content'] .= $content_suffix;
            $item['categories'] = $hashtags;

            // get post link
            $post_link = $post->find('a.post_link', 0)->getAttribute('href');
            preg_match('/wall-?\d+_(\d+)/', $post_link, $preg_match_result);
            $item['post_id'] = intval($preg_match_result[1]);
            $item['uri'] = $post_link;
            $item['timestamp'] = $this->getTime($post);
            $item['title'] = $this->getTitle($item['content']);
            $item['author'] = $post_author;
            $item['videos'] = $post_videos;
            if ($is_pinned_post) {
                // do not append it now
                $pinned_post_item = $item;
            } else {
                $last_post_id = $item['post_id'];
                $this->items[] = $item;
            }
        }

        if (!is_null($pinned_post_item)) {
            if (count($this->items) == 0) {
                $this->items[] = $pinned_post_item;
            } elseif ($last_post_id < $pinned_post_item['post_id']) {
                $this->items[] = $pinned_post_item;
                usort($this->items, function ($item1, $item2) {
                    return $item2['post_id'] - $item1['post_id'];
                });
            }
        }

        $this->getCleanVideoLinks();
    }

    private function getPhoto($a)
    {
        $onclick = $a->getAttribute('onclick');
        preg_match('/return showPhoto\(.+?({.*})/', $onclick, $preg_match_result);
        if (count($preg_match_result) == 0) {
            return;
        }

        $arg = htmlspecialchars_decode(str_replace('queue:1', '"queue":1', $preg_match_result[1]));
        $data = json_decode($arg, true);
        if ($data == null) {
            return;
        }

        $thumb = $data['temp']['base'] . $data['temp']['x_'][0];
        $original = '';
        foreach (['y_', 'z_', 'w_'] as $key) {
            if (!isset($data['temp'][$key])) {
                continue;
            }
            if (!isset($data['temp'][$key][0])) {
                continue;
            }
            if (substr($data['temp'][$key][0], 0, 4) == 'http') {
                $base = '';
            } else {
                $base = $data['temp']['base'];
            }
            $original = $base . $data['temp'][$key][0];
        }

        if ($original) {
            return "<a href='$original'><img src='$thumb'></a>";
        } else {
            return "<img src='$thumb'>";
        }
    }

    private function getTitle($content)
    {
        preg_match('/^["\w\ \p{L}\(\)\?#«»-]+/mu', htmlspecialchars_decode($content), $result);
        if (count($result) == 0) {
            return 'untitled';
        }
        return $result[0];
    }

    private function getTime($el)
    {
        if ($time = $el->getAttribute('time')) {
            return $time;
        } else {
            $strdate = $el->plaintext;
            $strdate = preg_replace('/[\x00-\x1F\x7F-\xFF]/', ' ', $strdate);

            $date = date_parse($strdate);
            if (!$date['year']) {
                if (strstr($strdate, 'today') !== false) {
                    $strdate = date('d-m-Y') . ' ' . $strdate;
                } elseif (strstr($strdate, 'yesterday ') !== false) {
                    $time = time() - 60 * 60 * 24;
                    $strdate = date('d-m-Y', $time) . ' ' . $strdate;
                } elseif ($date['month'] && intval(date('m')) < $date['month']) {
                    $strdate = $strdate . ' ' . (date('Y') - 1);
                } else {
                    $strdate = $strdate . ' ' . date('Y');
                }

                $date = date_parse($strdate);
            } elseif ($date['hour'] === false) {
                $date['hour'] = $date['minute'] = '00';
            }
            return strtotime($date['day'] . '-' . $date['month'] . '-' . $date['year'] . ' ' .
                $date['hour'] . ':' . $date['minute']);
        }
    }

    private function getContents()
    {
        $header = ['Accept-language: en', 'Cookie: remixlang=3'];
        $uri = $this->getURI();

        $redirects = 0;
        while ($redirects < 3) {
            try {
                return getContents($uri, $header, [CURLOPT_FOLLOWLOCATION => false]);
            } catch (\HttpException $e) {
                if ($e->getCode() != 301) {
                    throw $e;
                }

                $uri = urljoin(self::URI, $e->headers['location'][0]);
                if (!str_starts_with('https://vk.com/club') && !str_starts_with('https://vk.com/public')) {
                    throw $e;
                }
            }
            $redirects += 1;
        }

        returnServerError('Too many redirects, while retreving content from VK');
    }

    protected function appendVideo($video_title, $video_link, &$content_suffix, array &$post_videos)
    {
        if (!$video_title) {
            $video_title = '(empty)';
        }

        preg_match('/video([0-9-]+_[0-9]+)/', $video_link, $preg_match_result);

        if (count($preg_match_result) > 1) {
            $video_id = $preg_match_result[1];
            $this->videos[ $video_id ] = [
                'url' => $video_link,
                'title' => $video_title,
            ];
            $post_videos[] = $video_id;
        } else {
            $content_suffix .= '<br>Video: <a href="' . htmlspecialchars($video_link) . '">' . $video_title . '</a>';
        }
    }

    protected function getCleanVideoLinks()
    {
        if ($this->getOption('access_token')) {
            $result = $this->api('video.get', [
                'videos' => implode(',', array_keys($this->videos)),
                'count' => 200
            ]);

            foreach ($result['response']['items'] as $item) {
                $video_id = strval($item['owner_id']) . '_' . strval($item['id']);
                $this->videos[$video_id]['url'] = $item['player'];
            }
        }

        foreach ($this->items as &$item) {
            foreach ($item['videos'] as $video_id) {
                $video_link = $this->videos[$video_id]['url'];
                $video_title = $this->videos[$video_id]['title'];
                $item['content'] .= '<br>Video: <a href="' . htmlspecialchars($video_link) . '">' . $video_title . '</a>';
            }
            unset($item['videos']);
        }
    }

    protected function api($method, array $params)
    {
        $access_token = $this->getOption('access_token');
        if (!$access_token) {
            returnServerError('You cannot run VK API methods without access_token');
        }
        $params['v'] = '5.131';
        $params['access_token'] = $access_token;
        $r = json_decode(getContents('https://api.vk.com/method/' . $method . '?' . http_build_query($params)), true);
        if (isset($r['error'])) {
            returnServerError('API returned error: ' . $r['error']['error_msg'] . ' (' . $r['error']['error_code'] . ')');
        }
        return $r;
    }
}
