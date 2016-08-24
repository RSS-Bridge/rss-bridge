<?php
class IsoHuntBridge extends BridgeAbstract{
    public function loadMetadatas(){
        $this->maintainer = 'logmanoriginal';
        $this->name = 'isoHunt Bridge'; // Is replaced later!
        $this->uri = 'https://isohunt.to'; // Is replaced later!
        $this->description = 'Returns the latest results by category or search result';

        /*
        * Get feeds for one of the "latest" categories
        * Notice: The categories "News" and "Top Searches" are received from the main page
        * Elements are sorted by name ascending!
        */
        $this->parameters['By "Latest" category'] = array(
          'latest_category'=>array(
            'name'=>'Latest category',
            'type'=>'list',
            'required'=>true,
            'title'=>'Select your category',
            'defaultValue'=>'News',
            'values'=>array(
              'Hot Torrents'=>'hot_torrents',
              'News'=>'news',
              'Releases'=>'releases',
              'Torrents'=>'torrents'
            )
          )
        );

        /*
        * Get feeds for one of the "torrent" categories
        * Make sure to add new categories also to get_torrent_category_index($)!
        * Elements are sorted by name ascending!
        */
        $this->parameters['By "Torrent" category'] = array(
          'torrent_category'=>array(
            'name'=>'Torrent category',
            'type'=>'list',
            'required'=>true,
            'title'=>'Select your category',
            'defaultValue'=>'Anime',
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
        );

        /*
        * Get feeds for a specific search request
        */
        $this->parameters['Search torrent by name'] = array(
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
            'defaultValue'=>'All',
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
        );
    }

    public function collectData(){
        $params=$this->parameters[$this->queriedContext];
        $request_path = '/'; // We'll request the main page by default

        if(isset($params['latest_category']['value'])){ // Requesting one of the latest categories
            $this->request_latest_category($params['latest_category']['value']);
        } elseif(isset($params['torrent_category']['value'])){ // Requesting one of the torrent categories
            $order_popularity = false;

            if(isset($params['torrent_popularity']['value']))
                $order_popularity = $params['torrent_popularity']['value'];

            $this->request_torrent_category($params['torrent_category']['value'], $order_popularity);
        } else if(isset($params['search_name']['value'])){ // Requesting search
            if(isset($params['search_category']['value']))
                $this->request_search($params['search_name']['value'], $params['search_category']['value']);
            else
                $this->request_search($params['search_name']['value']);
        } else {
            $this->returnClientError('Unknown request!');
        }
    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }

#region Helper functions for "By "Torrent" category"

    private function request_torrent_category($category, $order_popularity){
        $category_name = $this->get_torrent_category_name($category);
        $category_index = $this->get_torrent_category_index($category);

        $this->name = 'Category: ' . $category_name . ' - ' . $this->name;
        $this->uri .= $this->build_category_uri($category_index, $order_popularity);

        $html = $this->load_html($this->uri);

        if(strtolower(trim($category)) === 'movies') // This one is special (content wise)
            $this->get_movie_torrents($html);
        else
            $this->get_latest_torrents($html);
    }

    private function get_torrent_category_name($category){
        $parameter = $this->parameters['By "Torrent" category'];
        $languages = $parameter['torrent_category']['values'];

        foreach($languages as $name=>$value)
            if(strtolower(trim($value)) === strtolower(trim($category)))
                return $name;

        return 'Unknown category';
    }

    private function get_torrent_category_index($category){
        switch(strtolower(trim($category))){
            case 'anime': return 1;
            case 'software' : return 2;
            case 'games' : return 3;
            case 'adult' : return 4;
            case 'movies' : return 5;
            case 'music' : return 6;
            case 'other' : return 7;
            case 'series_tv' : return 8;
            case 'books': return 9;
            default: return 0;
        }
    }

#endregion

    private function request_latest_category($category){
        switch($category){
            case 'hot_torrents': // This is a special case! (that's why return)
                $this->name = 'Latest hot torrents - ' . $this->name;
                $this->uri .= '/statistic/hot/torrents';
                $html = $this->load_html($this->uri);
                $this->get_latest_hot_torrents($html);
                return;
            case 'news': // This is a special case! (that's why return)
                $this->name = 'Latest news - ' . $this->name;
                $this->uri .= '/';
                $html = $this->load_html($this->uri);
                $this->get_latest_news($html);
                return;
            case 'releases':
                $this->name = 'Latest releases - ' . $this->name;
                $this->uri .= '/releases.php';
                break;
            case 'torrents':
                $this->name = 'Latest torrents - ' . $this->name;
                $this->uri .= '/latest.php';
                break;
            default: // No category applies
                $this->returnClientError('Undefined category: ' . $category . '!');
        }

        $html = $this->load_html($this->uri);
        $this->get_latest_torrents($html);
    }

#region Helper functions for "Search torrent by name"

    private function request_search($name, $category = 'all'){
        $category_name = $this->get_search_category_name($category);
        $category_index = $this->get_search_category_index($category);

        $this->name = 'Search: "' . $name . '" in category: ' . $category_name . ' - ' . $this->name;
        $this->uri .= $this->build_category_uri($category_index);

        if(strtolower(trim($category)) === 'movies'){ // This one is special (content wise)
            $html = $this->load_html($this->uri);
            $this->get_movie_torrents($html);
        } else {
            $this->uri .= '&ihq=' . urlencode($name);
            $html = $this->load_html($this->uri);
            $this->get_latest_torrents($html);
        }
    }

    private function get_search_category_name($category){
        $parameter = $this->parameters['Search torrent by name'];
        $languages = $parameter['search_category']['values'];

        foreach($languages as $name=>$value)
            if(strtolower(trim($value)) === strtolower(trim($category)))
                return $name;

        return 'Unknown category';
    }

    private function get_search_category_index($category){
        switch(strtolower(trim($category))){
            case 'all': return 0;
            default: return $this->get_torrent_category_index($category); // Uses the same index
        }
    }

#endregion

#region Helper functions for "Movie Torrents"

    private function get_movie_torrents($html){
        $container = $html->find('div#w0', 0);
        if(!$container)
            $this->returnServerError('Unable to find torrent container!');

        $torrents = $container->find('article');
        if(!$torrents)
            $this->returnServerError('Unable to find torrents!');

        foreach($torrents as $torrent){

            $anchor = $torrent->find('a', 0);
            if(!$anchor)
                $this->returnServerError('Unable to find anchor!');

            $date = $torrent->find('small', 0);
            if(!$date)
                $this->returnServerError('Unable to find date!');

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
            $this->returnServerError('Unable to find torrent container!');

        $torrents = $container->find('tr');
        if(!$torrents)
            $this->returnServerError('Unable to find torrents!');

        // Remove first element (header row)
        $torrents = array_slice($torrents, 1);

        foreach($torrents as $torrent){

            $cell = $torrent->find('td', 0);
            if(!$cell)
                $this->returnServerError('Unable to find cell!');

            $element = $cell->find('a', 0);
            if(!$element)
                $this->returnServerError('Unable to find element!');

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
            $this->returnServerError('Unable to find post container!');

        $posts = $container->find('div.index-post');
        if(!$posts)
            $this->returnServerError('Unable to find posts!');

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
            $this->returnServerError('Unable to find author!');

        // The author is hidden within a string like: 'Posted by {author} on {date}'
        preg_match('/Posted\sby\s(.*)\son/i', $author->innertext, $matches);

        return $matches[1];
    }

    private function latest_news_extract_timestamp($post){
        $date = $post->find('small', 0);
        if(!$date)
            $this->returnServerError('Unable to find date!');

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
            $this->returnServerError('Unable to find title!');

        return $title->plaintext;
    }

    private function latest_news_extract_uri($post){
        $uri = $post->find('a', 0);
        if(!$uri)
            $this->returnServerError('Unable to find uri!');

        return $uri->href;
    }

    private function latest_news_extract_content($post){
        $content = $post->find('div', 0);
        if(!$content)
            $this->returnServerError('Unable to find content!');

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
            $this->returnServerError('Unable to find torrent container!');

        $torrents = $container->find('tr[data-key]');
        if(!$torrents)
            $this->returnServerError('Unable to find torrents!');

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
            $this->returnServerError('Unable to find title cell!');

        $title = $cell->find('span', 0);
        if(!$title)
            $this->returnServerError('Unable to find title!');

        return $title->plaintext;
    }

    private function latest_torrents_extract_uri($torrent){
        $cell = $torrent->find('td.title-row', 0);
        if(!$cell)
            $this->returnServerError('Unable to find title cell!');

        $uri = $cell->find('a', 0);
        if(!$uri)
            $this->returnServerError('Unable to find uri!');

        return $this->fix_relative_uri($uri->href);
    }

    private function latest_torrents_extract_author($torrent){
        $cell = $torrent->find('td.user-row', 0);
        if(!$cell)
           return; // No author

        $user = $cell->find('a', 0);
        if(!$user)
            $this->returnServerError('Unable to find user!');

        return $user->plaintext;
    }

    private function latest_torrents_extract_timestamp($torrent){
        $cell = $torrent->find('td.date-row', 0);
        if(!$cell)
            $this->returnServerError('Unable to find date cell!');

        return strtotime('-' . $cell->plaintext, time());
    }

#endregion

#region Generic helper functions

    private function load_html($uri){
        $html = $this->getSimpleHTMLDOM($uri);
        if(!$html)
            $this->returnServerError('Unable to load ' . $uri . '!');

        return $html;
    }

    private function fix_relative_uri($uri){
        return preg_replace('/\//i', 'https://isohunt.to/', $uri, 1);
    }

    private function build_category_uri($index, $order_popularity = false){
        return '/torrents/?iht=' . $index . '&ihs=' . ($order_popularity ? 1 : 0) . '&age=0';
    }

#endregion
}
