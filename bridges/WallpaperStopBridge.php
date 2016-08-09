<?php
class WallpaperStopBridge extends BridgeAbstract {

    private $category;
    private $subcategory;
    private $resolution;

	public function loadMetadatas() {

		$this->maintainer = "nel50n";
		$this->name = "WallpaperStop Bridge";
		$this->uri = "http://www.wallpaperstop.com/";
		$this->description = "Returns the latests wallpapers from WallpaperStop";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "Category",
				"identifier" : "c"
			},
			{
				"name" : "subcategory",
				"identifier" : "s"
			},
			{
				"name" : "Max number of wallpapers",
				"identifier" : "m",
				"type" : "number"
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
        if (!isset($param['c'])) {
            $this->returnError('You must specify at least a category (?c=...).', 400);
        } else {
            $baseUri = 'http://www.wallpaperstop.com';

            $this->category = $param['c'];
            $this->subcategory = $param['s'] ?: '';
            $this->resolution = $param['r'] ?: '1920x1200';    // Wide wallpaper default

            $num = 0;
            $max = $param['m'] ?: 20;
            $lastpage = 1;

            for ($page = 1; $page <= $lastpage; $page++) {
                $link = $baseUri.'/'.$this->category.'-wallpaper/'.(!empty($this->subcategory)?$this->subcategory.'-wallpaper/':'').'desktop-wallpaper-'.$page.'.html';
                $html = $this->file_get_html($link) or $this->returnError('No results for this query.', 404);

                if ($page === 1) {
                    preg_match('/-(\d+)\.html$/', $html->find('.pagination > .last', 0)->href, $matches);
                    $lastpage = min($matches[1], ceil($max/20));
                }

                foreach($html->find('article.item') as $element) {
                    $wplink = $element->getAttribute('data-permalink');
                    if (preg_match('%^http://www\.wallpaperstop\.com/(.+)/([^/]+)-(\d+)\.html$%', $wplink, $matches)) {
                        $thumbnail = $element->find('img', 0);

                        $item = new \Item();
                        $item->uri = $baseUri.'/wallpapers/'.str_replace('wallpaper', 'wallpapers', $matches[1]).'/'.$matches[2].'-'.$this->resolution.'-'.$matches[3].'.jpg';
                        $item->id = $matches[3];
                        $item->timestamp = time();
                        $item->title = $thumbnail->title;
                        $item->content = $item->title.'<br><a href="'.$wplink.'"><img src="'.$baseUri.$thumbnail->src.'" /></a>';
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
