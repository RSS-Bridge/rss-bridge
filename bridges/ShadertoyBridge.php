<?php

class ShadertoyBridge extends BridgeAbstract
{
    const NAME = 'Shadertoy';
    const URI = 'https://www.shadertoy.com';
    const DESCRIPTION = 'Latest submissions on Shadertoy';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 3600; // 1h
    const PARAMETERS = [
        [
            'category' => [
                'name' => 'category',
                'type' => 'list',
                'exampleValue' => 'Popular',
                'title' => 'Select a category',
                'values' => [
                    'Shaders of the Week' => 'sotw',
                    'Popular' => 'popular',
                    'Newest' => 'newest',
                    'Hot' => 'hot',
                ]
            ]
        ]
    ];

    public function postprocessDescription($content)
    {
        // replace [url] tags
        $pattern = '/\[\/?url.*?\]/';
        $replace = '';
        $content = preg_replace($pattern, $replace, $content);

        // find URLs and turn then into hyperlinks
        $pattern = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
        $replace = '<a href="$0">$0</a>';
        $content = preg_replace($pattern, $replace, $content);

        return $content;
    }

    public function collectData()
    {
        $category = $this->getInput('category');
        $json = null;

        if ($category == 'sotw') {
            $url = static::URI . '/playlist/week';
            $contents = getContents($url);
            $shaderids = extractFromDelimiters($contents, 'var gShaderIDs = ', ';');
            $shaderids = str_replace('\'', '"', $shaderids);

            $url = static::URI . '/shadertoy';
            $data = 's=' . rawurlencode('{ "shaders": ' . $shaderids . ' }') . '&nt=0&nl=0&np=0';
            $header = [
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:135.0) Gecko/20100101 Firefox/135.0',
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: */*',
                'Origin: https://www.shadertoy.com',
                'Referer: https://www.shadertoy.com/playlist/week',
            ];

            $opts = [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => true
            ];
            $json = getContents($url, $header, $opts);
        } else {
            $url = static::URI . '/results?sort=' . $category;
            $contents = getContents($url);
            $json = extractFromDelimiters($contents, 'var gShaders=', 'var gUseScreenshots');
            $json = substr(trim($json), 0, -1);
        }

        $json = Json::decode($json);

        if (!$json) {
            throw new Exception(sprintf('Unable to find css selector on `%s`', static::URI));
        }

        foreach ($json as $article) {
            $id = $article['info']['id'];

            $title = $article['info']['name'];
            $author = $article['info']['username'];
            $uri = static::URI . '/view/' . $id;
            $content = '<p><img src="' . static::URI . '/media/shaders/' . $id . '.jpg"></p><p>' . $this->postprocessDescription($article['info']['description']) . '</p>';
            $timestamp = $article['info']['date'];

            $this->items[] = [
                'title' => $title,
                'author' => $author,
                'uri' => $uri,
                'content' => $content,
                'timestamp' => $timestamp,
            ];
        }
    }
}
