<?php
class PickyWallpapersBridge extends BridgeAbstract {

    private $category;
    private $subcategory;
    private $resolution;

	public $maintainer = "nel50n";
	public $name = "PickyWallpapers Bridge";
	public $uri = "http://www.pickywallpapers.com/";
	public $description = "Returns the latests wallpapers from PickyWallpapers";

    public $parameters = array( array(
        'c'=>array('name'=>'category'),
        's'=>array('name'=>'subcategory'),
        'm'=>array(
            'name'=>'Max number of wallpapers',
            'type'=>'number'
        ),
        'r'=>array(
            'name'=>'resolution',
            'exampleValue'=>'1920x1200, 1680x1050,…',
            'pattern'=>'[0-9]{3,4}x[0-9]{3,4}'
        )
    ));


    public function collectData(){
        $html = '';
        if (!isset($this->getInput('c'))) {
            $this->returnClientError('You must specify at least a category (?c=...).');
        } else {
            $baseUri = 'http://www.pickywallpapers.com';

            $this->category = $this->getInput('c');
            $this->subcategory = $this->getInput('s') ?: '';
            $this->resolution = $this->getInput('r') ?: '1920x1200';    // Wide wallpaper default

            $num = 0;
            $max = $this->getInput('m') ?: 12;
            $lastpage = 1;

            for ($page = 1; $page <= $lastpage; $page++) {
                $link = $baseUri.'/'.$this->resolution.'/'.$this->category.'/'.(!empty($this->subcategory)?$this->subcategory.'/':'').'page-'.$page.'/';
                $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('No results for this query.');

                if ($page === 1) {
                    preg_match('/page-(\d+)\/$/', $html->find('.pages li a', -2)->href, $matches);
                    $lastpage = min($matches[1], ceil($max/12));
                }

                foreach($html->find('.items li img') as $element) {

                    $item = array();
                    $item['uri'] = str_replace('www', 'wallpaper', $baseUri).'/'.$this->resolution.'/'.basename($element->src);
                    $item['timestamp'] = time();
                    $item['title'] = $element->alt;
                    $item['content'] = $item['title'].'<br><a href="'.$item['uri'].'">'.$element.'</a>';
                    $this->items[] = $item;

                    $num++;
                    if ($num >= $max)
                        break 2;
                }
            }
        }
    }

    public function getName(){
        return 'PickyWallpapers - '.$this->category.(!empty($this->subcategory) ? ' > '.$this->subcategory : '').' ['.$this->resolution.']';
    }

    public function getCacheDuration(){
        return 43200; // 12 hours
    }
}
