<?php
/**
*
* @name Ossietzky
* @homepage http://www.sopos.org/ossietzky/
* @description Zweiwochenschrift für Politik / Kultur / Wirtschaft
* @update 18/02/2015
* @maintainer: http://mro.name/me
*/
class OssietzkyBridge extends BridgeAbstract{

  public function collectData(array $param){
    // $html_homepage = file_get_html('http://www.sopos.org/ossietzky/archiv.php3') or $this->returnError('Could not request Ossietzky.', 404);    
    // foreach($html_homepage->find('.spoorahmklein a') as $issue_link) {
    $html_homepage = file_get_html('http://www.sopos.org/ossietzky/') or $this->returnError('Could not request Ossietzky.', 404);    
    foreach($html_homepage->find('html body table tbody tr td.tableborder p.nav small a') as $issue_link) {
      $html = file_get_html('http://www.sopos.org' . $issue_link->href) or $this->returnError('Could not request Ossietzky.', 404);         
      foreach($html->find('html body table tbody tr td.standardlink h1') as $issue_name) {
        $item_no = 0;
        preg_match('/([1-9][0-9]?)\\/([0-9][0-9][0-9][0-9])/', $issue_name->plaintext, $matches);
        foreach($html->find('html body table tbody tr td.standardlink p table.fullbordergrey tbody tr td b') as $element) {
          $item_no = $item_no + 1;
          // $comic = $element->find('img', 0);
          $item = new Item();
          $item->title = $matches[2] . '/' . $matches[1] . ': ' . $element->plaintext;
          $date_offset = ' + ' . (7*2*$matches[1]-11) . ' days';
          if( 0 >= strlen($element->parent->href) ) {
            // teaser-only entries:
            continue; // ignore 'em
            // assign a (early) dummy-date
            $item->timestamp = strtotime($matches[2] . '-01-01' . $date_offset);
            // assign a fake uri
            $item->uri = 'http://www.sopos.org/osietzky/' . $item_no;
          } else {
            // full-fledged entries:
            // assign a fake but sensible date
            $item->timestamp = strtotime($matches[2] . '-01-01 01:' . (59-$item_no) . $date_offset);
            $item->uri = 'http://www.sopos.org' . $element->parent->href;
            // pull in some deep content
            $content = file_get_html($item->uri) or $this->returnError('Could not request Ossietzky.', 404);
            $i = 0;
            foreach($content->find('html body table tbody tr td.standardlink p') as $con) {
              // if( 2 == $i )
              //  $item->name = trim($con->plaintext);
              if( 3 == $i ) {
                $item->content = trim($con->plaintext) . "\n\n...";
                break;
              }
              $i = $i + 1;
            }
          }
          $this->items[] = $item;
        }
      }
    }
  }

  public function getName(){
    return 'Ossietzky / Zweiwochenschrift';
  }

  public function getURI(){
    return 'http://www.sopos.org/ossietzky/';
  }

  public function getDescription(){
    return 'Ossietzky Zweiwochenschrift via rss-bridge';
  }

  public function getCacheDuration(){
    return 24*3600; // 24 hours
  }
}
?>
