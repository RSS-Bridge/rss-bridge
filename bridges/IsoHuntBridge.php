<?php
class IsoHuntBridge extends BridgeAbstract{
  const MAINTAINER = 'logmanoriginal';
  const NAME = 'isoHunt Bridge';
  const URI = 'https://isohunt.to/';
  const CACHE_TIMEOUT = 300; //5min
  const DESCRIPTION = 'Returns the latest results by category or search result';

  const PARAMETERS = array(
    /*
     * Get feeds for one of the "latest" categories
     * Notice: The categories "News" and "Top Searches" are received from the main page
     * Elements are sorted by name ascending!
     */
    'By "Latest" category' => array(
      'latest_category'=>array(
        'name'=>'Latest category',
        'type'=>'list',
        'required'=>true,
        'title'=>'Select your category',
        'defaultValue'=>'news',
        'values'=>array(
          'Hot Torrents'=>'hot_torrents',
          'News'=>'news',
          'Releases'=>'releases',
          'Torrents'=>'torrents'
        )
      )
    ),

    /*
     * Get feeds for one of the "torrent" categories
     * Make sure to add new categories also to get_torrent_category_index($)!
     * Elements are sorted by name ascending!
     */
    'By "Torrent" category' => array(
      'torrent_category'=>array(
        'name'=>'Torrent category',
        'type'=>'list',
        'required'=>true,
        'title'=>'Select your category',
        'defaultValue'=>'anime',
        'values'=>array(
          'Adult'=>'adult',
          'Anime'=>'anime',
          'Books'=>'books',
          'Games'=>'games',
          'Movies'=>'movies',
          'Music'=>'music',
          'Other'=>'other',
          'Series & TV'=>'series_tv',
          'Software'=>'software'
        )
      ),
      'torrent_popularity'=>array(
        'name'=>'Sort by popularity',
        'type'=>'checkbox',
        'title'=>'Activate to receive results by popularity'
      )
    ),

    /*
     * Get feeds for a specific search request
     */
    'Search torrent by name' => array(
      'search_name'=>array(
        'name'=>'Name',
        'required'=>true,
        'title'=>'Insert your search query',
        'exampleValue'=>'Bridge'
      ),
      'search_category'=>array(
        'name'=>'Category',
        'type'=>'list',
        'title'=>'Select your category',
        'defaultValue'=>'all',
        'values'=>array(
          'Adult'=>'adult',
          'All'=>'all',
          'Anime'=>'anime',
          'Books'=>'books',
          'Games'=>'games',
          'Movies'=>'movies',
          'Music'=>'music',
          'Other'=>'other',
          'Series & TV'=>'series_tv',
          'Software'=>'software'
        )
      )
    )
  );

  public  function getURI(){
    $uri=self::URI;
    switch($this->queriedContext){
    case 'By "Latest" category':
      switch($this->getInput('latest_category')){
      case 'hot_torrents':
        $uri .= 'statistic/hot/torrents';
        break;
      case 'news':
        break;
      case 'releases':
        $uri .= 'releases.php';
        break;
      case 'torrents':
        $uri .= 'latest.php';
        break;
      }
      break;

    case 'By "Torrent" category':
      $uri .= $this->build_category_uri(
        $this->getInput('torrent_category'),
        $this->getInput('torrent_popularity')
      );
      break;

    case 'Search torrent by name':
      $category=$this->getInput('search_category');
      $uri .= $this->build_category_uri($category);
      if($category!=='movies')
        $uri .= '&ihq=' . urlencode($this->getInput('search_name'));
      break;

    default: parent::getURI();
    }

    return $uri;
  }

  public  function getName(){
    switch($this->queriedContext){
    case 'By "Latest" category':
      $categoryName =
        array_search(
          $this->getInput('latest_category'),
          self::PARAMETERS['By "Latest" category']['latest_category']['values']
        );
      $name = 'Latest '.$categoryName.' - ' . self::NAME;
      break;

    case 'By "Torrent" category':
      $categoryName =
        array_search(
          $this->getInput('torrent_category'),
          self::PARAMETERS['By "Torrent" category']['torrent_category']['values']
        );
      $name = 'Category: ' . $categoryName . ' - ' . self::NAME;
      break;

    case 'Search torrent by name':
      $categoryName =
        array_search(
          $this->getInput('search_category'),
          self::PARAMETERS['Search torrent by name']['search_category']['values']
        );
      $name = 'Search: "' . $this->getInput('search_name') . '" in category: ' . $categoryName . ' - ' . self::NAME;
      break;

    default: return parent::getName();
    }

    return $name;
  }


  public function collectData(){
    $html = $this->load_html($this->getURI());

    switch($this->queriedContext){
    case 'By "Latest" category':
      switch($this->getInput('latest_category')){
      case 'hot_torrents':
        $this->get_latest_hot_torrents($html);
        break;
      case 'news':
        $this->get_latest_news($html);
        break;
      case 'releases':
      case 'torrents':
        $this->get_latest_torrents($html);
        break;
      }
      break;

    case 'By "Torrent" category':
      if($this->getInput('torrent_category') === 'movies'){
        // This one is special (content wise)
        $this->get_movie_torrents($html);
      }else{
        $this->get_latest_torrents($html);
      }
      break;

    case 'Search torrent by name':
      if( $this->getInput('search_category') === 'movies'){
        // This one is special (content wise)
        $this->get_movie_torrents($html);
      } else {
        $this->get_latest_torrents($html);
      }
      break;
    }
  }

  #region Helper functions for "Movie Torrents"

  private function get_movie_torrents($html){
    $container = $html->find('div#w0', 0);
    if(!$container)
      returnServerError('Unable to find torrent container!');

    $torrents = $container->find('article');
    if(!$torrents)
      returnServerError('Unable to find torrents!');

    foreach($torrents as $torrent){

      $anchor = $torrent->find('a', 0);
      if(!$anchor)
        returnServerError('Unable to find anchor!');

      $date = $torrent->find('small', 0);
      if(!$date)
        returnServerError('Unable to find date!');

      $item = array();

      $item['uri'] = $this->fix_relative_uri($anchor->href);
      $item['title'] = $anchor->title;
      // $item['author'] =
      $item['timestamp'] = strtotime($date->plaintext);
      $item['content'] = $this->fix_relative_uri($torrent->innertext);

      $this->items[] = $item;
    }
  }

  #endregion

  #region Helper functions for "Latest Hot Torrents"

  private function get_latest_hot_torrents($html){
    $container = $html->find('div#serps', 0);
    if(!$container)
      returnServerError('Unable to find torrent container!');

    $torrents = $container->find('tr');
    if(!$torrents)
      returnServerError('Unable to find torrents!');

    // Remove first element (header row)
    $torrents = array_slice($torrents, 1);

    foreach($torrents as $torrent){

      $cell = $torrent->find('td', 0);
      if(!$cell)
        returnServerError('Unable to find cell!');

      $element = $cell->find('a', 0);
      if(!$element)
        returnServerError('Unable to find element!');

      $item = array();

      $item['uri'] = $element->href;
      $item['title'] = $element->plaintext;
      // $item['author'] =
      // $item['timestamp'] =
      // $item['content'] =

      $this->items[] = $item;
    }
  }

  #endregion

  #region Helper functions for "Latest News"

  private function get_latest_news($html){
    $container = $html->find('div#postcontainer', 0);
    if(!$container)
      returnServerError('Unable to find post container!');

    $posts = $container->find('div.index-post');
    if(!$posts)
      returnServerError('Unable to find posts!');

    foreach($posts as $post){
      $item = array();

      $item['uri'] = $this->latest_news_extract_uri($post);
      $item['title'] = $this->latest_news_extract_title($post);
      $item['author'] = $this->latest_news_extract_author($post);
      $item['timestamp'] = $this->latest_news_extract_timestamp($post);
      $item['content'] = $this->latest_news_extract_content($post);

      $this->items[] = $item;
    }
  }

  private function latest_news_extract_author($post){
    $author = $post->find('small', 0);
    if(!$author)
      returnServerError('Unable to find author!');

    // The author is hidden within a string like: 'Posted by {author} on {date}'
    preg_match('/Posted\sby\s(.*)\son/i', $author->innertext, $matches);

    return $matches[1];
  }

  private function latest_news_extract_timestamp($post){
    $date = $post->find('small', 0);
    if(!$date)
      returnServerError('Unable to find date!');

    // The date is hidden within a string like: 'Posted by {author} on {date}'
    preg_match('/Posted\sby\s.*\son\s(.*)/i', $date->innertext, $matches);

    $timestamp = strtotime($matches[1]);

    // Make sure date is not in the future (dates are given like 'Nov. 20' without year)
    if($timestamp > time()){
      $timestamp = strtotime('-1 year', $timestamp);
    }

    return $timestamp;
  }

  private function latest_news_extract_title($post){
    $title = $post->find('a', 0);
    if(!$title)
      returnServerError('Unable to find title!');

    return $title->plaintext;
  }

  private function latest_news_extract_uri($post){
    $uri = $post->find('a', 0);
    if(!$uri)
      returnServerError('Unable to find uri!');

    return $uri->href;
  }

  private function latest_news_extract_content($post){
    $content = $post->find('div', 0);
    if(!$content)
      returnServerError('Unable to find content!');

    // Remove <h2>...</h2> (title)
    foreach($content->find('h2') as $element){
      $element->outertext = '';
    }

    // Remove <small>...</small> (author)
    foreach($content->find('small') as $element){
      $element->outertext = '';
    }

    return $content->innertext;
  }

  #endregion

  #region Helper functions for "Latest Torrents", "Latest Releases" and "Torrent Category"

  private function get_latest_torrents($html){
    $container = $html->find('div#serps', 0);
    if(!$container)
      returnServerError('Unable to find torrent container!');

    $torrents = $container->find('tr[data-key]');
    if(!$torrents)
      returnServerError('Unable to find torrents!');

    foreach($torrents as $torrent){
      $item = array();

      $item['uri'] = $this->latest_torrents_extract_uri($torrent);
      $item['title'] = $this->latest_torrents_extract_title($torrent);
      $item['author'] = $this->latest_torrents_extract_author($torrent);
      $item['timestamp'] = $this->latest_torrents_extract_timestamp($torrent);
      $item['content'] = ''; // There is no valuable content

      $this->items[] = $item;
    }
  }

  private function latest_torrents_extract_title($torrent){
    $cell = $torrent->find('td.title-row', 0);
    if(!$cell)
      returnServerError('Unable to find title cell!');

    $title = $cell->find('span', 0);
    if(!$title)
      returnServerError('Unable to find title!');

    return $title->plaintext;
  }

  private function latest_torrents_extract_uri($torrent){
    $cell = $torrent->find('td.title-row', 0);
    if(!$cell)
      returnServerError('Unable to find title cell!');

    $uri = $cell->find('a', 0);
    if(!$uri)
      returnServerError('Unable to find uri!');

    return $this->fix_relative_uri($uri->href);
  }

  private function latest_torrents_extract_author($torrent){
    $cell = $torrent->find('td.user-row', 0);
    if(!$cell)
      return; // No author

    $user = $cell->find('a', 0);
    if(!$user)
      returnServerError('Unable to find user!');

    return $user->plaintext;
  }

  private function latest_torrents_extract_timestamp($torrent){
    $cell = $torrent->find('td.date-row', 0);
    if(!$cell)
      returnServerError('Unable to find date cell!');

    return strtotime('-' . $cell->plaintext, time());
  }

  #endregion

  #region Generic helper functions

  private function load_html($uri){
    $html = getSimpleHTMLDOM($uri);
    if(!$html)
      returnServerError('Unable to load ' . $uri . '!');

    return $html;
  }

  private function fix_relative_uri($uri){
    return preg_replace('/\//i', self::URI, $uri, 1);
  }

  private function build_category_uri($category, $order_popularity = false){
    switch($category){
    case 'anime': $index = 1; break;
    case 'software' : $index = 2; break;
    case 'games' : $index = 3; break;
    case 'adult' : $index = 4; break;
    case 'movies' : $index = 5; break;
    case 'music' : $index = 6; break;
    case 'other' : $index = 7; break;
    case 'series_tv' : $index = 8; break;
    case 'books': $index = 9; break;
    case 'all':
    default: $index = 0; break;
    }

    return 'torrents/?iht=' . $index . '&ihs=' . ($order_popularity ? 1 : 0) . '&age=0';
  }

  #endregion
}
