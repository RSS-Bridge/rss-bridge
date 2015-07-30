<?php

/**
 * A class providing facilities for RSS expansion. The goal here is to facilitate, as much as possible, writing bridges such as FreeNews, Gawker and other ones 
 * @name RssExpander 
 * @description Un bridge générique d'expansion automatique de contenu RSS ... pour tous ces sites qui ont un flux RSS mochement tonqué.
 * @update 15/03/2015
 * @use1(url="URL du flux dont vous souhaitez le contenu complet")
 */
 
abstract class RssExpander extends HttpCachingBridgeAbstract{
    protected $name;
    private $uri;
    private $description;
    public function collectData(array $param){
        if (empty($param['url'])) {
            $this->returnError('There is no $param[\'url\'] for this RSS expander', 404);
        }
//       $this->message("Loading from ".$param['url']);
        // Notice WE DO NOT use cache here on purpose : we want a fresh view of the RSS stream each time
        $rssContent = simplexml_load_file($param['url']) or $this->returnError('Could not request '.$param['url'], 404);
//        $this->message("loaded RSS from ".$param['url']);
        // TODO insert RSS format detection
        // we suppose for now, we have some RSS 2.0
        $this->collect_RSS_2_0_data($rssContent);
    }
    
    protected function collect_RSS_2_0_data($rssContent) {
        $rssContent = $rssContent->channel[0];
//        $this->message("RSS content is ===========\n".var_export($rssContent, true)."===========");
        $this->load_RSS_2_0_feed_data($rssContent);
        foreach($rssContent->item as $item) {
//            $this->message("parsing item ".var_export($item, true));
            $this->items[] = $this->parseRSSItem($item);
        }
    }
    
    protected function RSS_2_0_time_to_timestamp($item)  {
        return DateTime::createFromFormat('D, d M Y H:i:s e', $item->pubDate)->getTimestamp();
    }
    
    // TODO set title, link, description, language, and so on
    protected function load_RSS_2_0_feed_data($rssContent) {
        $this->name = trim($rssContent->title);
        $this->uri = trim($rssContent->link);
        $this->description = trim($rssContent->description);
    }
    
    /**
     * Method should return, from a source RSS item given by lastRSS, one of our Items objects
     * @param $item the input rss item
     * @return a RSS-Bridge Item, with (hopefully) the whole content)
     */
    abstract protected function parseRSSItem($item);

    
    public function getName(){
        return $this->name;
    }

    public function getURI(){
        return $this->uri;
    }
    
    public function getDescription() {
        return $this->description;
    }
}