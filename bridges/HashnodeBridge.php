<?php

class HashnodeBridge extends BridgeAbstract
{
    const MAINTAINER = 'liamka';
    const NAME = 'Hashnode';
    const URI = 'https://hashnode.com';
    const CACHE_TIMEOUT = 3600; // 1hr
    const DESCRIPTION = 'See trending or latest posts in Hashnode community.';
    const LATEST_POSTS = 'https://hashnode.com/api/stories/recent?page=';

    public function collectData()
    {
        $this->items = [];
        for ($i = 0; $i < 5; $i++) {
            $url = self::LATEST_POSTS . $i;
            $content = getContents($url);
            $array = json_decode($content, true);

            if ($array['posts'] != null) {
                foreach ($array['posts'] as $post) {
                    $item = [];
                    $item['title'] = $post['title'];
                    $item['content'] = nl2br(htmlspecialchars($post['brief']));
                    $item['timestamp'] = $post['dateAdded'];
                    if ($post['partOfPublication'] === true) {
                        $item['uri'] = sprintf(
                            'https://%s.hashnode.dev/%s',
                            $post['publication']['username'],
                            $post['slug']
                        );
                    } else {
                        $item['uri'] = sprintf('https://hashnode.com/post/%s', $post['slug']);
                    }
                    if (!isset($item['uri'])) {
                        continue;
                    }
                    $this->items[] = $item;
                }
            }
        }
    }

    public function getName()
    {
        return self::NAME . ': Recent posts';
    }
}
