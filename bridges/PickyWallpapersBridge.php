<?php
class PickyWallpapersBridge extends BridgeAbstract {

    private $category;
    private $subcategory;
    private $resolution;

	public function loadMetadatas() {

		$this->maintainer = "nel50n";
		$this->name = "PickyWallpapers Bridge";
		$this->uri = "http://www.pickywallpapers.com/";
		$this->description = "Returns the latests wallpapers from PickyWallpapers";

        $this->parameters[] = array(
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
        );

	}

    public function collectData(array $param){
        $html = '';
        if (!isset($param['c'])) {
            $this->returnClientError('You must specify at least a category (?c=...).');
        } else {
            $baseUri = 'http://www.pickywallpapers.com';

            $this->category = $param['c'];
            $this->subcategory = $param['s'] ?: '';
            $this->resolution = $param['r'] ?: '1920x1200';    // Wide wallpaper default

            $num = 0;
            $max = $param['m'] ?: 12;
            $lastpage = 1;

            for ($page = 1; $page <= $lastpage; $page++) {
                $link = $baseUri.'/'.$this->resolution.'/'.$this->category.'/'.(!empty($this->subcategory)?$this->subcategory.'/':'').'page-'.$page.'/';
                $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('No results for this query.');

                if ($page === 1) {
                    preg_match('/page-(\d+)\/$/', $html->find('.pages li a', -2)->href, $matches);
                    $lastpage = min($matches[1], ceil($max/12));
                }

                foreach($html->find('.items li img') as $element) {

                    $item = new \Item();
                    $item->uri = str_replace('www', 'wallpaper', $baseUri).'/'.$this->resolution.'/'.basename($element->src);
                    $item->timestamp = time();
                    $item->title = $element->alt;
                    $item->content = $item->title.'<br><a href="'.$item->uri.'">'.$element.'</a>';
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
