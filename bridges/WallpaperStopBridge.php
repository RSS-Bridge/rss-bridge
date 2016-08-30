<?php
class WallpaperStopBridge extends BridgeAbstract {

    private $category;
    private $subcategory;
    private $resolution;

	public $maintainer = "nel50n";
	public $name = "WallpaperStop Bridge";
	public $uri = "http://www.wallpaperstop.com/";
	public $description = "Returns the latests wallpapers from WallpaperStop";

    public $parameters = array( array(
        'c'=>array('name'=>'Category'),
        's'=>array('name'=>'subcategory'),
        'm'=>array(
            'name'=>'Max number of wallpapers',
            'type'=>'number'
        ),
        'r'=>array(
            'name'=>'resolution',
            'exampleValue'=>'1920x1200, 1680x1050,…',
        )
    ));


    public function collectData(){
        $html = '';
        if (!$this->getInput('c')) {
            $this->returnClientError('You must specify at least a category (?c=...).');
        } else {
            $baseUri = 'http://www.wallpaperstop.com';

            $this->category = $this->getInput('c');
            $this->subcategory = $this->getInput('s') ?: '';
            $this->resolution = $this->getInput('r') ?: '1920x1200';    // Wide wallpaper default

            $num = 0;
            $max = $this->getInput('m') ?: 20;
            $lastpage = 1;

            for ($page = 1; $page <= $lastpage; $page++) {
                $link = $baseUri.'/'.$this->category.'-wallpaper/'.(!empty($this->subcategory)?$this->subcategory.'-wallpaper/':'').'desktop-wallpaper-'.$page.'.html';
                $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('No results for this query.');

                if ($page === 1) {
                    preg_match('/-(\d+)\.html$/', $html->find('.pagination > .last', 0)->href, $matches);
                    $lastpage = min($matches[1], ceil($max/20));
                }

                foreach($html->find('article.item') as $element) {
                    $wplink = $element->getAttribute('data-permalink');
                    if (preg_match('%^http://www\.wallpaperstop\.com/(.+)/([^/]+)-(\d+)\.html$%', $wplink, $matches)) {
                        $thumbnail = $element->find('img', 0);

                        $item = array();
                        $item['uri'] = $baseUri.'/wallpapers/'.str_replace('wallpaper', 'wallpapers', $matches[1]).'/'.$matches[2].'-'.$this->resolution.'-'.$matches[3].'.jpg';
                        $item['id'] = $matches[3];
                        $item['timestamp'] = time();
                        $item['title'] = $thumbnail->title;
                        $item['content'] = $item['title'].'<br><a href="'.$wplink.'"><img src="'.$baseUri.$thumbnail->src.'" /></a>';
                        $this->items[] = $item;

                        $num++;
                        if ($num >= $max)
                            break 2;
                    }

                }
            }
        }
    }

    public function getName(){
        return 'WallpaperStop - '.$this->category.(!empty($this->subcategory) ? ' > '.$this->subcategory : '').' ['.$this->resolution.']';
    }

    public function getCacheDuration(){
        return 43200; // 12 hours
    }
}
