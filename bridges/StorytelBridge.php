<?php 
class StorytelBridge extends BridgeAbstract { 
    const NAME = 'Storytel List Bridge'; 
    const URI = 'https://www.storytel.com/tr'; 
    const DESCRIPTION = 'Fetches books from a Storytel list, including title, author, and cover image.'; 
    const MAINTAINER = 'Okbaydere'; 
    const PARAMETERS = [ 
        'List' => [ 
            'url' => [ 
                'name' => 'Storytel List URL', 
                'required' => true, 
                'exampleValue' => 'https://www.storytel.com/tr/lists/23d09e0bd8fe4d998d1832ddbfa18166', 
            ], 
        ], 
    ]; 

    public function collectData() { 
        $url = $this->getInput('url'); 
        $html = getSimpleHTMLDOM($url); 

        if (!$html) { 
            returnServerError('Unable to fetch Storytel list'); 
        } 

        foreach ($html->find('li.sc-4615116a-1') as $element) { 
            $item = []; 

            // Fetch the title 
            $titleElement = $element->find('span.sc-b1963858-0.hoTsmF', 0); 
            $item['title'] = $titleElement ? $titleElement->plaintext : 'No title'; 

            // Fetch the author 
            $authorElement = $element->find('span.sc-b1963858-0.ghYMwH', 0); 
            $item['author'] = $authorElement ? $authorElement->plaintext : 'Unknown author'; 

            // Fetch the cover image 
            $imgElement = $element->find('img.sc-da400893-5', 0); 
            $coverUrl = $imgElement ? $imgElement->getAttribute('srcset') : ''; 
            if ($coverUrl) { 
                // Select the highest resolution cover image 
                $coverUrls = explode(', ', $coverUrl); 
                $bestCoverUrl = trim(end($coverUrls)); 
                $item['content'] = '<img src="' . preg_replace('/\?.*/', '', $bestCoverUrl) . '"/>'; 
            } 

            // Fetch the link (details page) 
            $linkElement = $element->find('a', 0); 
            $item['uri'] = $linkElement ? 'https://www.storytel.com' . $linkElement->getAttribute('href') : $url; 

            // Combine content 
            $item['content'] .= '<p>Author: ' . $item['author'] . '</p>'; 
            $item['content'] .= '<p><a href="' . $item['uri'] . '">More details</a></p>'; 

            $this->items[] = $item; 
        } 
    } 
} 
?>
