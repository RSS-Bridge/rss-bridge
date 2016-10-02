<?php
class SuperbWallpapersBridge extends BridgeAbstract {

	const MAINTAINER = "nel50n";
	const NAME = "Superb Wallpapers Bridge";
	const URI = "http://www.superbwallpapers.com/";
	const CACHE_TIMEOUT = 43200; // 12h
	const DESCRIPTION = "Returns the latests wallpapers from SuperbWallpapers";

    const PARAMETERS = array( array(
      'c'=>array(
        'name'=>'category',
        'required'=>true
      ),
        'm'=>array(
            'name'=>'Max number of wallpapers',
            'type'=>'number'
        ),
        'r'=>array(
            'name'=>'resolution',
            'exampleValue'=>'1920x1200, 1680x1050,…',
            'defaultValue'=>'1920x1200'
        )
    ));

    public function collectData(){
        $category   = $this->getInput('c');
        $resolution = $this->getInput('r');  // Wide wallpaper default

        $num = 0;
        $max = $this->getInput('m') ?: 36;
        $lastpage = 1;

        // Get last page number
        $link = self::URI.'/'.$category.'/9999.html';
        $html = getSimpleHTMLDOM($link)
          or returnServerError('Could not load '.$link);

        $lastpage = min($html->find('.paging .cpage', 0)->innertext(), ceil($max/36));

        for ($page = 1; $page <= $lastpage; $page++) {
            $link = self::URI.'/'.$category.'/'.$page.'.html';
            $html = getSimpleHTMLDOM($link)
              or returnServerError('No results for this query.');

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
        return self::NAME .'- '.$this->getInput('c').' ['.$this->getInput('r').']';
    }
}
