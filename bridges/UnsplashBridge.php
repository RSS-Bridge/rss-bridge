<?php
/**
* UnsplashBridge
* Returns the latests photos from http://unsplash.com
*
* @name Unsplash Bridge
* @homepage http://unsplash.com/
* @description Returns the latests photos from Unsplash
* @maintainer nel50n
* @update 2015-03-02
* @use1(m="max number of photos",w="width (1920, 1680, ...)",q="jpeg quality (0..100)")
*/
class UnsplashBridge extends BridgeAbstract {

    public function collectData(array $param){
        $html = '';
        $baseUri = 'http://unsplash.com';

        $width = $param['w'] ?: '1920';    // Default width

        $num = 0;
        $max = $param['m'] ?: 20;
        $quality = $param['q'] ?: 75;
        $lastpage = 1;

        for ($page = 1; $page <= $lastpage; $page++) {
            $link = $baseUri.'/grid?page='.$page;
            $html = file_get_html($link) or $this->returnError('No results for this query.', 404);

            if ($page === 1) {
                preg_match('/=(\d+)$/', $html->find('.pagination > a[!class]', -1)->href, $matches);
                $lastpage = min($matches[1], ceil($max/40));
            }

            foreach($html->find('.photo') as $element) {
                $thumbnail = $element->find('img', 0);
                $thumbnail->src = str_replace('https://', 'http://', $thumbnail->src);

                $item = new \Item();
                $item->uri = str_replace(array('q=75', 'w=400'),
                                         array("q=$quality", "w=$width"),
                                         $thumbnail->src).'.jpg';           // '.jpg' only for format hint
                $item->timestamp = time();
                $item->title = $thumbnail->alt;
                $item->thumbnailUri = $thumbnail->src;
                $item->content = $item->title.'<br><a href="'.$item->uri.'"><img src="'.$item->thumbnailUri.'" /></a>';
                $this->items[] = $item;

                $num++;
                if ($num >= $max)
                    break 2;
            }
        }
    }

    public function getName(){
        return 'Unsplash';
    }

    public function getURI(){
        return 'http://unsplash.com';
    }

    public function getCacheDuration(){
        return 43200; // 12 hours
    }
}
