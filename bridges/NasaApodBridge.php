<?php
class NasaApodBridge extends BridgeAbstract{

	const MAINTAINER = "corenting";
	const NAME = "NASA APOD Bridge";
	const URI = "http://apod.nasa.gov/apod/";
	const CACHE_TIMEOUT = 43200; // 12h
	const DESCRIPTION = "Returns the 3 latest NASA APOD pictures and explanations";

  public function collectData(){

    $html = getSimpleHTMLDOM(self::URI.'archivepix.html') or returnServerError('Error while downloading the website content');
    $list = explode("<br>", $html->find('b', 0)->innertext);

    for($i = 0; $i < 3;$i++)
    {
      $line = $list[$i];
      $item = array();

      $uri_page = $html->find('a',$i + 3)->href;
      $uri = self::URI.$uri_page;
      $item['uri'] = $uri;

      $picture_html = getSimpleHTMLDOM($uri);
      $picture_html_string = $picture_html->innertext;

      //Extract image and explanation
      $media = $picture_html->find('p',1)->innertext;
      $media = strstr($media, '<br>');
      $media = preg_replace('/<br>/', '', $media, 1);
      $explanation = $picture_html->find('p',2)->innertext;

      //Extract date from the picture page
      $date = explode(" ", $picture_html->find('p',1)->innertext);
      $item['timestamp'] = strtotime($date[4].$date[3].$date[2]);

      //Other informations
      $item['content'] = $media.'<br />'.$explanation;
      $item['title'] = $picture_html->find('b',0)->innertext;
      $this->items[] = $item;
    }
  }
}
