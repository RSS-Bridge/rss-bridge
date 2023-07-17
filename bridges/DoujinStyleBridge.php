<?php

class DoujinStyleBridge extends BridgeAbstract {
    const NAME = 'DoujinStyle Bridge';
    const URI = 'https://doujinstyle.com/';
    const DESCRIPTION = 'Returns submissions from DoujinStyle';
    const MAINTAINER = 'mrtnvgr';

    // TODO: "Games" support
    // TODO: Search support

    public function collectData() {
        $html = getSimpleHTMLDOM($this->getURI());
        $html = defaultLinkTo($html, $this->getURI());

        $submissions = $html->find('.gridBox .gridDetails');
        foreach ($submissions as $submission) {
            $item = array();

            $item['uri'] = $submission->find('a', 0)->href;

            $content = getSimpleHTMLDOM($item['uri']);
            $content = defaultLinkTo($content, $this->getURI());

            $item['title'] = $content->find('h2', 0)->plaintext;

            $cover = $content->find('#imgClick a', 0);
            if (is_null($cover)) {
                $cover = $content->find('.coverWrap', 0)->src;
            } else {
                $cover = $cover->href;
            }

            $item['content'] = "<img src='$cover'/>";

            $keys = [];
            foreach ($content->find(".pageWrap .pageSpan1") as $key) {
                $keys[] = $key->plaintext; 
            }

            $values = $content->find(".pageWrap .pageSpan2");
            $metadata = array_combine($keys, $values);

            $format = 'Unknown';

            foreach ($metadata as $key => $value) {
                switch ($key) {
                    case 'Artist':
                        $item['author'] = $value->find('a', 0)->plaintext;
                        break;
                    case 'Tags:':
                        $item['categories'] = [];
                        foreach ($value->find("a") as $tag) {
                            $item['categories'][] = $tag->plaintext;
                        }
                        break;
                    case 'Format:':
                        $format = $value->plaintext;
                        break;
                    case 'Date Added:':
                        $item['timestamp'] = $value->plaintext;
                        break;
                }
            }
            
            $item['content'] .= "<p>Format: $format</p>";

            $this->items[] = $item;
        }
    }
}

