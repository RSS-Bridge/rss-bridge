<?php
class UnsplashBridge extends BridgeAbstract {

	public $maintainer = "nel50n";
	public $name = "Unsplash Bridge";
	public $uri = "http://unsplash.com/";
	public $description = "Returns the latests photos from Unsplash";

    public $parameters = array( array(
          'm'=>array(
            'name'=>'Max number of photos',
            'type'=>'number',
            'defaultValue'=>20
          ),
          'w'=>array(
            'name'=>'Width',
            'exampleValue'=>'1920, 1680, …',
            'defaultValue'=>'1920'
          ),
          'q'=>array(
            'name'=>'JPEG quality',
            'type'=>'number',
            'defaultValue'=>75
          )
      ));

    public function collectData(){
        $width = $this->getInput('w') ;
        $num = 0;
        $max = $this->getInput('m');
        $quality = $this->getInput('q');
        $lastpage = 1;

        for ($page = 1; $page <= $lastpage; $page++) {
            $link = $this->uri.'/grid?page='.$page;
            $html = $this->getSimpleHTMLDOM($link)
              or $this->returnServerError('No results for this query.');

            if ($page === 1) {
                preg_match('/=(\d+)$/', $html->find('.pagination > a[!class]', -1)->href, $matches);
                $lastpage = min($matches[1], ceil($max/40));
            }

            foreach($html->find('.photo') as $element) {
                $thumbnail = $element->find('img', 0);
                $thumbnail->src = str_replace('https://', 'http://', $thumbnail->src);

                $item = array();
                $item['uri'] = str_replace(array('q=75', 'w=400'),
                                         array("q=$quality", "w=$width"),
                                         $thumbnail->src).'.jpg';           // '.jpg' only for format hint
                $item['timestamp'] = time();
                $item['title'] = $thumbnail->alt;
                $item['content'] = $item['title'].'<br><a href="'.$item['uri'].'"><img src="'.$thumbnail->src.'" /></a>';
                $this->items[] = $item;

                $num++;
                if ($num >= $max)
                    break 2;
            }
        }
    }

    public function getCacheDuration(){
        return 43200; // 12 hours
    }
}
