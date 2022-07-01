<?php

class PickyWallpapersBridge extends BridgeAbstract
{
    const MAINTAINER = 'nel50n';
    const NAME = 'PickyWallpapers Bridge';
    const URI = 'https://www.pickywallpapers.com/';
    const CACHE_TIMEOUT = 43200; // 12h
    const DESCRIPTION = 'Returns the latests wallpapers from PickyWallpapers';

    const PARAMETERS = [ [
        'c' => [
            'name' => 'category',
            'exampleValue' => 'funny',
            'required' => true
        ],
        's' => [
            'name' => 'subcategory'
        ],
        'm' => [
            'name' => 'Max number of wallpapers',
            'defaultValue' => 12,
            'type' => 'number'
        ],
        'r' => [
            'name' => 'resolution',
            'exampleValue' => '1920x1200, 1680x1050,…',
            'defaultValue' => '1920x1200',
            'pattern' => '[0-9]{3,4}x[0-9]{3,4}'
        ]
    ]];

    public function collectData()
    {
        $lastpage = 1;
        $num = 0;
        $max = $this->getInput('m');
        $resolution = $this->getInput('r'); // Wide wallpaper default

        for ($page = 1; $page <= $lastpage; $page++) {
            $html = getSimpleHTMLDOM($this->getURI() . '/page-' . $page . '/');

            if ($page === 1) {
                preg_match('/page-(\d+)\/$/', $html->find('.pages li a', -2)->href, $matches);
                $lastpage = min($matches[1], ceil($max / 12));
            }

            foreach ($html->find('.items li img') as $element) {
                $item = [];
                $item['uri'] = str_replace('www', 'wallpaper', self::URI)
                . '/'
                . $resolution
                . '/'
                . basename($element->src);

                $item['timestamp'] = time();
                $item['title'] = $element->alt;
                $item['content'] = $item['title']
                . '<br><a href="'
                . $item['uri']
                . '">'
                . $element
                . '</a>';

                $this->items[] = $item;

                $num++;
                if ($num >= $max) {
                    break 2;
                }
            }
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('s')) && !is_null($this->getInput('r')) && !is_null($this->getInput('c'))) {
            $subcategory = $this->getInput('s');
            $link = self::URI
            . $this->getInput('r')
            . '/'
            . $this->getInput('c')
            . '/'
            . $subcategory;

            return $link;
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('s'))) {
            $subcategory = $this->getInput('s');
            return 'PickyWallpapers - '
            . $this->getInput('c')
            . ($subcategory ? ' > ' . $subcategory : '')
            . ' ['
            . $this->getInput('r')
            . ']';
        }

        return parent::getName();
    }
}
