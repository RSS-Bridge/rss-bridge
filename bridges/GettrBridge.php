<?php

class GettrBridge extends BridgeAbstract
{
    const NAME = 'Gettr.com bridge';
    const URI = 'https://gettr.com';
    const DESCRIPTION = 'Fetches the latest posts from a GETTR user';
    const MAINTAINER = 'dvikan';
    const CACHE_TIMEOUT = 60 * 15; // 15m
    const PARAMETERS = [
        [
            'user' => [
                'name' => 'User',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'joerogan',
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'title' => 'Maximum number of items to return (maximum 20)',
                'defaultValue' => 5,
                'required' => true,
            ],
        ]
    ];

    public function collectData()
    {
        $api = sprintf(
            'https://api.gettr.com/u/user/%s/posts?offset=0&max=%s&dir=fwd&incl=posts&fp=f_uo',
            $this->getInput('user'),
            min($this->getInput('limit'), 20)
        );
        $data = json_decode(getContents($api), false);

        foreach ($data->result->aux->post as $post) {
            $this->items[] = [
                'title' => mb_substr($post->txt ?? $post->uid . '@gettr.com', 0, 100),
                'uri' => sprintf('https://gettr.com/post/%s', $post->_id),
                'author' => $post->uid,
                // Convert from ms to s
                'timestamp' => substr($post->cdate, 0, strlen($post->cdate) - 3),
                'uid' => $post->_id,
                // Hashtags found within post text
                'categories' => $post->htgs ?? [],
                'content' => $this->createContent($post),
            ];
        }
    }

    /**
     * Collect text, image and video, if they exist
     */
    private function createContent(\stdClass $post): string
    {
        $content = '';

        // Text
        if (isset($post->txt)) {
            $isRepost = $this->getInput('user') !== $post->uid;
            if ($isRepost) {
                $content .= 'Reposted by ' . $this->getInput('user') . '@gettr.com<br><br>';
            }
            $content .= "$post->txt <br><br>";
        }

        // Preview image
        if (isset($post->previmg)) {
            $content .= <<<HTML
<a href="$post->prevsrc" target="_blank">
    <img
        src='$post->previmg'
        alt='Unable to load image'
        loading='lazy'
    >
</a>
<br><br>
HTML;
        }

        // Images
        foreach ($post->imgs ?? [] as $imageUrl) {
            $content .= <<<HTML
<img
    src='https://media.gettr.com/$imageUrl'
    alt='Unable to load image'
    target='_blank'
>
<br><br>
HTML;
        }

        // Video
        if (isset($post->ovid)) {
            $mainImage = $post->main;

            $content .= <<<HTML
<video
    style="max-width: 100%"
    controls
    preload="none"
    poster="https://media.gettr.com/$mainImage"
>
  <source src="https://media.gettr.com/$post->ovid" type="video/mp4">
  Your browser does not support the video element. Kindly update it to latest version.
</video >
HTML;
            // This is typically a m3u8 which I don't know how to present in a browser
            $streamingUrl = $post->vid;
        }
        $this->processMetadata($post);

        return $content;
    }

    public function getIcon()
    {
        return 'https://gettr.com/favicon.ico';
    }

    /**
     * @param stdClass $post
     */
    private function processMetadata(stdClass $post): void
    {
        // Unused metadata, maybe used later
        $textLanguage = $post->txt_lang ?? 'en';
        $replies = $post->cm ?? 0;
        $likes = $post->lkbpst ?? 0;
        $reposts = $post->shbpst ?? 0;
        // I think a visibility of "p" means that it's public
        $visibility = $post->vis ?? 'p';
    }
}
