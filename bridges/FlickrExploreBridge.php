<?php
class FlickrExploreBridge extends BridgeAbstract{

	public $maintainer = "sebsauvage";
	public $name = "Flickr Explore";
	public $uri = "https://www.flickr.com/";
	public $description = "Returns the latest interesting images from Flickr";

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->uri.'explore')
            or $this->returnServerError('Could not request Flickr.');

        foreach($html->find('.photo-list-photo-view') as $element) {
						// Get the styles
						$style = explode(';', $element->style);
						// Get the background-image style
						$backgroundImage = explode(':', end($style));
						// URI type : url(//cX.staticflickr.com/X/XXXXX/XXXXXXXXX.jpg)
						$imageURI = trim(str_replace(['url(', ')'], '', end($backgroundImage)));
						// Get the image ID
						$imageURIs = explode('_', basename($imageURI));
						$imageID = reset($imageURIs);

						// Get the image JSON via Flickr API
                        $imageJSON = json_decode($this->getContents(
                            'https://api.flickr.com/services/rest/?'
                            .'method=flickr.photos.getInfo&'
                            .'api_key=103b574d49bd51f0e18bfe907da44a0f&'
                            .'photo_id='.$imageID.'&'
                            .'format=json&'
                            .'nojsoncallback=1'
                        )) or $this->returnServerError('Could not request Flickr.'); // FIXME: Request time too long...

            $item = array();
            $item['uri'] = $this->uri.'photo.gne?id='.$imageID;
            $item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $imageURI . '" /></a>'; // FIXME: Filter javascript ?
            $item['title'] = $imageJSON->photo->title->_content;
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
