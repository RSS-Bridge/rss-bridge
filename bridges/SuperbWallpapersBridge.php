<?php
class SuperbWallpapersBridge extends BridgeAbstract {

    private $category;
    private $resolution;

	public $maintainer = "nel50n";
	public $name = "Superb Wallpapers Bridge";
	public $uri = "http://www.superbwallpapers.com/";
	public $description = "Returns the latests wallpapers from SuperbWallpapers";

    public $parameters = array( array(
        'c'=>array('name'=>'category'),
        'm'=>array(
            'name'=>'Max number of wallpapers',
            'type'=>'number'
        ),
        'r'=>array(
            'name'=>'resolution',
            'exampleValue'=>'1920x1200, 1680x1050,…'
        )
    ));

    public function collectData(){
        $html = '';
        $baseUri = 'http://www.superbwallpapers.com';

        $this->category   = $this->getInput('c') ?: '';           // All default
        $this->resolution = $this->getInput('r') ?: '1920x1200';  // Wide wallpaper default

        $num = 0;
        $max = $this->getInput('m') ?: 36;
        $lastpage = 1;

        // Get last page number
        $link = $baseUri.'/'.$this->category.'/9999.html';
        $html = $this->getSimpleHTMLDOM($link);
        $lastpage = min($html->find('.paging .cpage', 0)->innertext(), ceil($max/36));

        for ($page = 1; $page <= $lastpage; $page++) {
            $link = $baseUri.'/'.$this->category.'/'.$page.'.html';
            $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('No results for this query.');

            foreach($html->find('.wpl .i a') as $element) {
                $thumbnail = $element->find('img', 0);

                $item = array();
                $item['uri'] = str_replace('200x125', $this->resolution, $thumbnail->src);
                $item['timestamp'] = time();
                $item['title'] = $element->title;
                $item['content'] = $item['title'].'<br><a href="'.$item['uri'].'">'.$thumbnail.'</a>';
                $this->items[] = $item;

                $num++;
                if ($num >= $max)
                    break 2;
            }
        }
    }

    public function getName(){
        return 'HDWallpapers - '.$this->category.' ['.$this->resolution.']';
    }

    public function getCacheDuration(){
        return 43200; // 12 hours
    }
}
