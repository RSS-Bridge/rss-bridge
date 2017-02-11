<?php
class FlickrExploreBridge extends BridgeAbstract {

    const MAINTAINER = "sebsauvage";
    const NAME = "Flickr Explore";
    const URI = "https://www.flickr.com/";
    const CACHE_TIMEOUT = 21600; // 6 hours
    const DESCRIPTION = "Returns the latest interesting images from Flickr";

    public function collectData(){
        $html = getSimpleHTMLDOM(self::URI . 'explore')
            or returnServerError('Could not request Flickr.');

        // Find SCRIPT containing JSON data
        $model = $html->find('.modelExport', 0);
        $model_text = $model->innertext;

        // Find start and end of JSON data
        $start = strpos($model_text, 'modelExport:') + strlen('modelExport:');
        $end = strpos($model_text, 'auth:') - strlen('auth:');

        // Dissect JSON data and remove trailing comma
        $model_text = trim(substr($model_text, $start, $end - $start));
        $model_text = substr($model_text, 0, strlen($model_text) - 1);

        $model_json = json_decode($model_text, true);

        foreach($html->find('.photo-list-photo-view') as $element){
            // Get the styles
            $style = explode(';', $element->style);

            // Get the background-image style
            $backgroundImage = explode(':', end($style));

            // URI type : url(//cX.staticflickr.com/X/XXXXX/XXXXXXXXX.jpg)
            $imageURI = trim(str_replace(['url(', ')'], '', end($backgroundImage)));

            // Get the image ID
            $imageURIs = explode('_', basename($imageURI));
            $imageID = reset($imageURIs);

            // Use JSON data to build items
            foreach(reset($model_json)[0]['photos']['_data'] as $element){
                if($element['id'] === $imageID){
                    $item = array();
                    $item['author'] = (array_key_exists('username', $element) ? $element['username'] : 'Anonymous');
                    $item['title'] = (array_key_exists('title', $element) ? $element['title'] : 'Untitled');
                    $item['uri'] = self::URI . 'photo.gne?id=' . $imageID;

                    $description = (array_key_exists('description', $element) ? $element['description'] : '');

                    $item['content'] = '<a href="'
                    . $item['uri']
                    . '"><img src="'
                    . $imageURI . '" /></a>'
                    . '<br>'
                    . '<p>'
                    . $description
                    . '</p>';

                    $this->items[] = $item;

                    break;
                }
            }
        }
    }
}
