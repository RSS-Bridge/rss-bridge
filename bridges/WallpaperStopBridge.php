<?php
class WallpaperStopBridge extends BridgeAbstract {

	const MAINTAINER = "nel50n";
	const NAME = "WallpaperStop Bridge";
	const URI = "http://www.wallpaperstop.com";
	const CACHE_TIMEOUT = 43200; // 12h
	const DESCRIPTION = "Returns the latests wallpapers from WallpaperStop";

    const PARAMETERS = array( array(
        'c'=>array('name'=>'Category'),
        's'=>array('name'=>'subcategory'),
        'm'=>array(
            'name'=>'Max number of wallpapers',
            'type'=>'number',
            'defaultValue'=>20
        ),
        'r'=>array(
            'name'=>'resolution',
            'exampleValue'=>'1920x1200, 1680x1050,…',
            'defaultValue'=>'1920x1200'
        )
    ));


    public function collectData(){
       $category = $this->getInput('c');
       $subcategory = $this->getInput('s');
       $resolution = $this->getInput('r');

       $num = 0;
       $max = $this->getInput('m');
       $lastpage = 1;

       for ($page = 1; $page <= $lastpage; $page++) {
           $link = self::URI.'/'.$category.'-wallpaper/'.(!empty($subcategory)?$subcategory.'-wallpaper/':'').'desktop-wallpaper-'.$page.'.html';
           $html = getSimpleHTMLDOM($link)
             or returnServerError('No results for this query.');

           if ($page === 1) {
               preg_match('/-(\d+)\.html$/', $html->find('.pagination > .last', 0)->href, $matches);
               $lastpage = min($matches[1], ceil($max/20));
           }

           foreach($html->find('article.item') as $element) {
               $wplink = $element->getAttribute('data-permalink');
               if (preg_match('%^'.self::URI.'/(.+)/([^/]+)-(\d+)\.html$%', $wplink, $matches)) {
                   $thumbnail = $element->find('img', 0);

                   $item = array();
                   $item['uri'] = self::URI.'/wallpapers/'.str_replace('wallpaper', 'wallpapers', $matches[1]).'/'.$matches[2].'-'.$resolution.'-'.$matches[3].'.jpg';
                   $item['id'] = $matches[3];
                   $item['timestamp'] = time();
                   $item['title'] = $thumbnail->title;
                   $item['content'] = $item['title'].'<br><a href="'.$wplink.'"><img src="'.self::URI.$thumbnail->src.'" /></a>';
                   $this->items[] = $item;

                   $num++;
                   if ($num >= $max)
                       break 2;
               }
           }
       }
    }

    public function getName(){
        $subcategory=$this->getInput('s');
        return 'WallpaperStop - '.$this->getInput('c').(!empty($subcategory) ? ' > '.$subcategory : '').' ['.$this->getInput('r').']';
    }
}
