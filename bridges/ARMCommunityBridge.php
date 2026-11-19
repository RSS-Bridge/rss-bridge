<?php

declare(strict_types=1);

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
                    'Announcements' => 'announcements',
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
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:140.0) Gecko/20100101 Firefox/140.0',
        ];

        $html = getSimpleHTMLDOM(static::URI . $category, $header);
        $html = defaultLinkTo($html, static::URI);

        foreach ($html->find('ads-card') as $c) {
            $articleurl = static::URI . $c->link;
            $articlehtml = getSimpleHTMLDOMCached($articleurl, static::CACHE_TIMEOUT, $header);

            $date = strtotime($articlehtml->find('#blog-date', 0)->innertext);
            $title = $articlehtml->find('#blog-title', 0)->innertext;
            $author = $articlehtml->find('#blog-title', 0)->parent->find('p', 1)->find('a', 0)->innertext;
            $content = $articlehtml->find('#blog-body', 0)->innertext;

            $this->items[] = [
                'title'      => $title,
                'timestamp'  => $date,
                'author'     => $author,
                'uri'        => $articleurl,
                'content'    => $content,
            ];
        }
    }

    public function getName()
    {
        $categoryname = $this->getKey('community');

        if (empty($categoryname)) {
            return static::NAME;
        }

        return static::NAME . ' - ' . $categoryname;
    }
}
