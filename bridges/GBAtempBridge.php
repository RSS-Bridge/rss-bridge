<?php
class GBAtempBridge extends BridgeAbstract {

    private $filter = '';

    public function loadMetadatas() {

        $this->maintainer = 'ORelio';
        $this->name = 'GBAtemp';
        $this->uri = $this->getURI();
        $this->description = 'GBAtemp is a user friendly underground video game community.';
        $this->update = '2016-02-14';

        $this->parameters[] =
        '[
            {
                "name" : "Type",
                "type" : "list",
                "identifier" : "type",
                "values" :
                [
                    {
                        "name" : "News",
                        "value" : "N"
                    },
                    {
                        "name" : "Reviews",
                        "value" : "R"
                    },
                    {
                        "name" : "Tutorials",
                        "value" : "T"
                    },
                    {
                        "name" : "Forum",
                        "value" : "F"
                    }
                ]
            }
        ]';
    }

    public function collectData(array $param) {

        function ExtractFromDelimiters($string, $start, $end) {
            if (strpos($string, $start) !== false) {
                $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
                $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
                return $section_retrieved;
            } return false;
        }

        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        function build_item($uri, $title, $author, $timestamp, $thumnail, $content) {
            $item = new \Item();
            $item->uri = $uri;
            $item->title = $title;
            $item->author = $author;
            $item->timestamp = $timestamp;
            $item->thumbnailUri = $thumnail;
            $item->content = $content;
            return $item;
        }

        function cleanup_post_content($content, $site_url) {
            $content = str_replace(':arrow:', '&#x27a4;', $content);
            $content = str_replace('href="attachments/', 'href="'.$site_url.'attachments/', $content);
            $content = StripWithDelimiters($content, '<script', '</script>');
            return $content;
        }

        function fetch_post_content($uri, $site_url) {
            $html = file_get_html($uri) or $this->returnError('Could not request GBAtemp: '.$uri, 500);
            $content = $html->find('div.messageContent', 0)->innertext;
            return cleanup_post_content($content, $site_url);
        }

        $typeFilter = '';
        if (!empty($param['type'])) {
            if ($param['type'] == 'N' || $param['type'] == 'R' || $param['type'] == 'T' || $param['type'] == 'F') {
                $typeFilter = $param['type'];
                if ($typeFilter == 'N') { $this->filter = 'News'; }
                if ($typeFilter == 'R') { $this->filter = 'Review'; }
                if ($typeFilter == 'T') { $this->filter = 'Tutorial'; }
                if ($typeFilter == 'F') { $this->filter = 'Forum'; }
            } else $this->returnError('The provided type filter is invalid. Expecting N, R, T, or F.', 400);
        } else $this->returnError('Please provide a type filter. Expecting N, R, T, or F.', 400);

        $html = file_get_html($this->getURI()) or $this->returnError('Could not request GBAtemp.', 500);

        if ($typeFilter == 'N') {
            foreach ($html->find('li[class=news_item full]') as $newsItem) {
                $url = $this->getURI().$newsItem->find('a', 0)->href;
                $img = $this->getURI().$newsItem->find('img', 0)->src;
                $time = intval(ExtractFromDelimiters($newsItem->find('abbr.DateTime', 0)->outertext, 'data-time="', '"'));
                $author = $newsItem->find('a.username', 0)->plaintext;
                $title = $newsItem->find('a', 1)->plaintext;
                $content = fetch_post_content($url, $this->getURI());
                $this->items[] = build_item($url, $title, $author, $time, $img, $content);
            }
        } else if ($typeFilter == 'R') {
            foreach ($html->find('li.portal_review') as $reviewItem) {
                $url = $this->getURI().$reviewItem->find('a', 0)->href;
                $img = $this->getURI().ExtractFromDelimiters($reviewItem->find('a', 0)->style, 'image:url(', ')');
                $title = $reviewItem->find('span.review_title', 0)->plaintext;
                $content = file_get_html($url) or $this->returnError('Could not request GBAtemp: '.$uri, 500);
                $author = $content->find('a.username', 0)->plaintext;
                $time = intval(ExtractFromDelimiters($content->find('abbr.DateTime', 0)->outertext, 'data-time="', '"'));
                $intro = '<p><b>'.($content->find('div#review_intro', 0)->plaintext).'</b></p>';
                $review = $content->find('div#review_main', 0)->innertext;
                $subheader = '<p><b>'.$content->find('div.review_subheader', 0)->plaintext.'</b></p>';
                $procons = $content->find('table.review_procons', 0)->outertext;
                $scores = $content->find('table.reviewscores', 0)->outertext;
                $content = cleanup_post_content($intro.$review.$subheader.$procons.$scores, $this->getURI());
                $this->items[] = build_item($url, $title, $author, $time, $img, $content);
            }
        } else if ($typeFilter == 'T') {
            foreach ($html->find('li.portal-tutorial') as $tutorialItem) {
                $url = $this->getURI().$tutorialItem->find('a', 0)->href;
                $title = $tutorialItem->find('a', 0)->plaintext;
                $time = intval(ExtractFromDelimiters($tutorialItem->find('abbr.DateTime', 0)->outertext, 'data-time="', '"'));
                $author = $tutorialItem->find('a.username', 0)->plaintext;
                $content = fetch_post_content($url, $this->getURI());
                $this->items[] = build_item($url, $title, $author, $time, '', $content);
            }
        } else if ($typeFilter == 'F') {
            foreach ($html->find('li.rc_item') as $postItem) {
                $url = $this->getURI().$postItem->find('a', 1)->href;
                $title = $postItem->find('a', 1)->plaintext;
                $time = intval(ExtractFromDelimiters($postItem->find('abbr.DateTime', 0)->outertext, 'data-time="', '"'));
                $author = $postItem->find('a.username', 0)->plaintext;
                $content = fetch_post_content($url, $this->getURI());
                $this->items[] = build_item($url, $title, $author, $time, '', $content);
            }
        }
    }

    public function getName() {
        return 'GBAtemp'.(empty($this->filter) ? '' : ' '.$this->filter).' Bridge';
    }

    public function getURI() {
        return 'http://gbatemp.net/';
    }

    public function getCacheDuration() {
        return ($this->filter === 'Forum') ? 300 : 3600; // 5 minutes / 1 hour
    }

}
