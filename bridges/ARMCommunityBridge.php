<?php

class ARMCommunityBridge extends BridgeAbstract
{
    const MAINTAINER = 'thefranke';
    const NAME = 'ARM Community';
    const URI = 'https://developer.arm.com';
    const CACHE_TIMEOUT = 86400; // 24h
    const DESCRIPTION = 'A bridge for the ARM Community blog';

    const PARAMETERS = [
        'Blog' => [
            'community' => [
                'name' => 'Community',
                'type' => 'list',
                'values' => [
                    'AI' => 'ai-blog',
                    'Annoucements' => 'annoucements',
                    'Architectures and Processors' => 'architectures-and-processors-blog',
                    'Automotive' => 'automotive-blog',
                    'Embedded and Microcontrollers' => 'embedded-and-microcontrollers-blog',
                    'Internet of Things (IoT)' => 'internet-of-things-blog',
                    'Laptops and Desktops' => 'laptops-and-desktops-blog',
                    'Mobile, Graphics, and Gaming' => 'mobile-graphics-and-gaming-blog',
                    'Operating Systems' => 'operating-systems-blog',
                    'Server and Cloud Computing' => 'servers-and-cloud-computing-blog',
                    'SoC Design and Simulation' => 'soc-design-and-simulation-blog',
                    'Tools, Software and IDEs' => 'tools-software-ides-blog',
                ],
            ]
        ]
    ];

    public function collectData()
    {
        $category = '/community/arm-community-blogs/b/' . $this->getInput('community');

        $header = [
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en',
            'Accept-Encoding: gzip, deflate, br, zstd',
        ];

        $html = getSimpleHTMLDOM(static::URI . $category, $header);

        // paste scripts together
        $jsonstr = '';
        $pattern = '/\(\[.."(.*?)"\]\)/';
        foreach ($html->find('script') as $s) {
            if (!str_starts_with($s->innertext, 'self.__next_f.push')) {
                continue;
            }

            if (preg_match($pattern, $s->innertext, $matches)) {
                $jsonstr .= $matches[1];
            }
        }

        $exp = explode('\\n', $jsonstr);

        $pattern = '/"link":"' . addcslashes($category, '/') . '(.*?)"/';
        foreach ($exp as $e) {
            if (preg_match($pattern, stripcslashes($e), $matches)) {
                $articleurl = static::URI . $category . $matches[1];
                $html = getSimpleHTMLDOMCached($articleurl, static::CACHE_TIMEOUT, $header);

                $date = strtotime($html->find('#blog-date', 0)->innertext);
                $title = $html->find('#blog-title', 0)->innertext;
                $author = $html->find('#blog-title', 0)->parent->find('p', 1)->find('a', 0)->innertext;
                $content = $html->find('#blog-body', 0)->innertext;
                $categories = $html->find('.c-tag', 0)->innertext;

                $this->items[] = [
                    'title'      => $title,
                    'timestamp'  => $date,
                    'author'     => $author,
                    'uri'        => $articleurl,
                    'content'    => $content,
                    'categories' => [$categories],
                ];
            }
        }
    }

    public function getName()
    {
        return static::NAME . ': ' . $this->getKey('community');
    }
}
