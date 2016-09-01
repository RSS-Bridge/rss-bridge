<?php
class WallpaperStopBridge extends BridgeAbstract {

	public $maintainer = "nel50n";
	public $name = "WallpaperStop Bridge";
	public $uri = "http://www.wallpaperstop.com";
	public $description = "Returns the latests wallpapers from WallpaperStop";

    public $parameters = array( array(
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
           $link = $this->uri.'/'.$category.'-wallpaper/'.(!empty($subcategory)?$subcategory.'-wallpaper/':'').'desktop-wallpaper-'.$page.'.html';
           $html = $this->getSimpleHTMLDOM($link)
             or $this->returnServerError('No results for this query.');

           if ($page === 1) {
               preg_match('/-(\d+)\.html$/', $html->find('.pagination > .last', 0)->href, $matches);
               $lastpage = min($matches[1], ceil($max/20));
           }

           foreach($html->find('article.item') as $element) {
               $wplink = $element->getAttribute('data-permalink');
               if (preg_match('%^'.$this->uri.'/(.+)/([^/]+)-(\d+)\.html$%', $wplink, $matches)) {
                   $thumbnail = $element->find('img', 0);

                   $item = array();
                   $item['uri'] = $this->uri.'/wallpapers/'.str_replace('wallpaper', 'wallpapers', $matches[1]).'/'.$matches[2].'-'.$resolution.'-'.$matches[3].'.jpg';
                   $item['id'] = $matches[3];
                   $item['timestamp'] = time();
                   $item['title'] = $thumbnail->title;
                   $item['content'] = $item['title'].'<br><a href="'.$wplink.'"><img src="'.$this->uri.$thumbnail->src.'" /></a>';
                   $this->items[] = $item;

                   $num++;
                   if ($num >= $max)
                       break 2;
               }
           }
       }
    }

    public function getName(){
        return 'WallpaperStop - '.$this->getInput('c').(!empty($this->getInput('s')) ? ' > '.$this->getInput('s') : '').' ['.$this->getInput('r').']';
    }

    public function getCacheDuration(){
        return 43200; // 12 hours
    }
}
