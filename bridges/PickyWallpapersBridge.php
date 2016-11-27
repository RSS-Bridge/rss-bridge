<?php
class PickyWallpapersBridge extends BridgeAbstract {

	const MAINTAINER = "nel50n";
	const NAME = "PickyWallpapers Bridge";
	const URI = "http://www.pickywallpapers.com/";
	const CACHE_TIMEOUT = 43200; // 12h
	const DESCRIPTION = "Returns the latests wallpapers from PickyWallpapers";

    const PARAMETERS = array( array(
      'c'=>array(
        'name'=>'category',
        'required'=>true
      ),
        's'=>array('name'=>'subcategory'),
        'm'=>array(
            'name'=>'Max number of wallpapers',
            'defaultValue'=>12,
            'type'=>'number'
        ),
        'r'=>array(
            'name'=>'resolution',
            'exampleValue'=>'1920x1200, 1680x1050,…',
            'defaultValue'=>'1920x1200',
            'pattern'=>'[0-9]{3,4}x[0-9]{3,4}'
        )
    ));


    public function collectData(){
        $lastpage = 1;
        $num = 0;
        $max = $this->getInput('m');
        $resolution = $this->getInput('r');    // Wide wallpaper default

        for ($page = 1; $page <= $lastpage; $page++) {
          $html = getSimpleHTMLDOM($this->getURI().'/page-'.$page.'/')
            or returnServerError('No results for this query.');

            if ($page === 1) {
                preg_match('/page-(\d+)\/$/', $html->find('.pages li a', -2)->href, $matches);
                $lastpage = min($matches[1], ceil($max/12));
            }

            foreach($html->find('.items li img') as $element) {

                $item = array();
                $item['uri'] = str_replace('www', 'wallpaper', self::URI).'/'.$resolution.'/'.basename($element->src);
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

    public function getURI(){
        $subcategory = $this->getInput('s');
        $link = self::URI.$this->getInput('r').'/'.$this->getInput('c').'/'.$subcategory;
        return $link;
    }

    public function getName(){
        $subcategory = $this->getInput('s');
        return 'PickyWallpapers - '.$this->getInput('c')
          .($subcategory? ' > '.$subcategory : '')
          .' ['.$this->getInput('r').']';
    }
}
