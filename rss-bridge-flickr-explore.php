<?php
/* Flickr Explorer RSS bridge.
   Returns a feed all new interesting images from http://www.flickr.com/explore
   Licence: Public domain.
   Returns ATOM feed by default.
   Other available formats: ?format=plaintext and ?format=json
*/
ini_set('user_agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:20.0) Gecko/20100101 Firefox/20.0');
date_default_timezone_set('UTC');
//ini_set('display_errors','1');
//error_reporting(E_ALL);

function returnError($code, $message) { header("HTTP/1.1 $code"); header('content-type: text/plain'); die($message); }

if (!file_exists('simple_html_dom.php')) { returnError('404 Not Found', 'ERROR: "PHP Simple HTML DOM Parser" is missing. Get it from http://simplehtmldom.sourceforge.net/  and place the script "simple_html_dom.php" in the same folder to allow me to work.'); }
require_once('simple_html_dom.php');

$html = file_get_html('http://www.flickr.com/explore') or returnError('404 Not Found', 'ERROR: could not request Flickr');
$items = Array();
foreach($html->find('span.photo_container') as $element) 
{
    $item['href'] = 'http://flickr.com'.$element->find('a',0)->href;  // Page URI
    $item['thumbnailUri'] = $element->find('img',0)->getAttribute('data-defer-src');  // Thumbnail URI
    $item['title'] = $element->find('a',0)->title;  // Photo title
    $items[] = $item;
}

if(empty($items)) { returnError('404 Not Found', 'ERROR: no results.'); }
$format = 'atom';
if (!empty($_GET['format'])) { $format = $_GET['format']; }
switch($format) 
{
    case 'plaintext':
    case 'json':
    case 'atom':
        break;
    default:
        $format='atom';
}

if($format == 'plaintext') { header('content-type: text/plain;charset=utf8'); print_r($items); exit; }
if($format == 'json') { header('content-type: application/json'); $items = json_encode($items); exit($items); }
if($format == 'atom') 
{
    header('content-type: application/atom+xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xml:lang="en-US">'."\n";
    echo '<title type="text">Flickr Explore</title>'."\n";
    echo '<id>http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '')."://{$_SERVER['HTTP_HOST']}{$_SERVER['PATH_INFO']}".'/</id>'."\n";
    echo '<updated>'.date(DATE_ATOM, $tweets['0']['timestamp']).'</updated>'."\n";
    echo '<link rel="alternate" type="text/html" href="http://www.flickr.com/explore" />'."\n";
    echo '<link rel="self" href="http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '')."://{$_SERVER['HTTP_HOST']}".htmlentities($_SERVER['REQUEST_URI']).'" />'."\n"."\n";

    foreach($items as $item) {
        echo '<entry><author><name>Flickr</name><uri>http://flickr.com/</uri></author>'."\n";
        echo '<title type="html"><![CDATA['.$item['title'].']]></title>'."\n";
        echo '<link rel="alternate" type="text/html" href="'.$item['href'].'" />'."\n";
        echo '<id>'.$item['href'].'</id>'."\n";
        echo '<updated></updated>'."\n"; // FIXME: date ???
        echo '<content type="html"><![CDATA[<a href="'.$item['href'].'"><img src="'.$item['thumbnailUri'].'" /></a>]]></content>'."\n";
        echo '</entry>'."\n\n";
        }
    echo '</feed>';
    exit;
}

exit();
