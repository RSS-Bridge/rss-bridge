<?php
class HDWallpapersBridge extends BridgeAbstract {

    private $category;
    private $resolution;

	public function loadMetadatas() {

		$this->maintainer = "nel50n";
		$this->name = "HD Wallpapers Bridge";
		$this->uri = "http://www.hdwallpapers.in/";
		$this->description = "Returns the latests wallpapers from HDWallpapers";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "category",
				"identifier" : "c"
			},
			{
				"name" : "max number of wallpapers",
				"identifier" : "m"
			},
			{
				"name" : "resolution",
				"identifier" : "r",
				"exampleValue" : "1920x1200, 1680x1050, ..."
			}
		]';
	}

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
            $html = $this->file_get_html($link) or $this->returnError('No results for this query.', 404);

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
                $item->content = $item->title.'<br><a href="'.$item->uri.'"><img src="'.$baseUri.$thumbnail->src.'" /></a>';
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

    public function getCacheDuration(){
        return 43200; // 12 hours
    }
}
