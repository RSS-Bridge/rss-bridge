<?php
/**
* HDWallpapersBridge
* Returns the latests wallpapers from http://www.hdwallpapers.in
*
* @name HD Wallpapers Bridge
* @homepage http://www.hdwallpapers.in/
* @description Returns the latests wallpapers from HDWallpapers
* @maintainer nel50n
* @update 2015-04-08
* @use1(c="category",m="max number of wallpapers",r="resolution (1920x1200, 1680x1050, ...)")
*/
class HDWallpapersBridge extends BridgeAbstract {

    private $category;
    private $resolution;

    public function collectData(array $param){
        $html = '';
        $baseUri = 'http://www.hdwallpapers.in';

        $this->category   = $param['c'] ?: 'latest_wallpapers'; // Latest default
        $this->resolution = $param['r'] ?: '1920x1200';         // Wide wallpaper default

        $category = $this->category;
        if (strrpos($category, 'wallpapers') !== strlen($category)-strlen('wallpapers')) {
            $category .= '-desktop-wallpapers';
        }

        $num = 0;
        $max = $param['m'] ?: 14;
        $lastpage = 1;

        for ($page = 1; $page <= $lastpage; $page++) {
            $link = $baseUri.'/'.$category.'/page/'.$page;
            $html = file_get_html($link) or $this->returnError('No results for this query.', 404);

            if ($page === 1) {
                preg_match('/page\/(\d+)$/', $html->find('.pagination a', -2)->href, $matches);
                $lastpage = min($matches[1], ceil($max/14));
            }

            foreach($html->find('.wallpapers .wall a') as $element) {
                $thumbnail = $element->find('img', 0);

                $item = new \Item();
                // http://www.hdwallpapers.in/download/yosemite_reflections-1680x1050.jpg
                $item->uri = $baseUri.'/download'.str_replace('wallpapers.html', $this->resolution.'.jpg', $element->href);
                $item->timestamp = time();
                $item->title = $element->find('p', 0)->text();
                $item->thumbnailUri = $baseUri.$thumbnail->src;
                $item->content = $item->title.'<br><a href="'.$item->uri.'"><img src="'.$item->thumbnailUri.'" /></a>';
                $this->items[] = $item;

                $num++;
                if ($num >= $max)
                    break 2;
            }
        }
    }

    public function getName(){
        return 'HDWallpapers - '.str_replace(['__', '_'], [' & ', ' '], $this->category).' ['.$this->resolution.']';
    }

    public function getURI(){
        return 'http://www.hdwallpapers.in';
    }

    public function getCacheDuration(){
        return 43200; // 12 hours
    }
}
