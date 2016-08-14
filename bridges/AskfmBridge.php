<?php
class AskfmBridge extends BridgeAbstract{

        public function loadMetadatas() {

                $this->maintainer = "az5he6ch";
                $this->name = "Ask.fm Answers";
                $this->uri = "http://ask.fm/";
                $this->description = "Returns answers from an Ask.fm user";

                $this->parameters["Ask.fm username"] =
                '[
                        {
                                "name" : "Username",
                                "identifier" : "u"
                        }
                ]';
        }

    public function collectData(array $param){
        $html = '';
        if (isset($param['u'])) {
            $this->request = $param['u'];
            $html = $this->file_get_html('http://ask.fm/'.urlencode($this->request).'/answers/more?page=0') or $this->returnError('Requested username can\'t be found.', 404);
        }
        else {
            $this->returnError('You must specify a username (?u=...).', 400);
        }

        foreach($html->find('div.streamItem-answer') as $element) {
            $item = new \Item();
            $item->uri = 'http://ask.fm'.$element->find('a.streamItemsAge',0)->href;
            $question = trim($element->find('h1.streamItemContent-question',0)->innertext);
            $item->title = trim(htmlspecialchars_decode($element->find('h1.streamItemContent-question',0)->plaintext, ENT_QUOTES));
            $answer = trim($element->find('p.streamItemContent-answer',0)->innertext);
            #$item->update = $element->find('a.streamitemsage',0)->data-hint; // Doesn't work, DOM parser doesn't seem to like data-hint, dunno why
            $visual = $element->find('div.streamItemContent-visual',0)->innertext; // This probably should be cleaned up, especially for YouTube embeds
            //Fix tracking links, also doesn't work
            foreach($element->find('a') as $link) {
                if (strpos($link->href, 'l.ask.fm') !== false) {
                    #$link->href = str_replace('#_=_', '', get_headers($link->href, 1)['Location']); // Too slow
                    $link->href = $link->plaintext;
                }
            }
            $content = '<p>' . $question . '</p><p>' . $answer . '</p><p>' . $visual . '</p>';
            // Fix relative links without breaking // scheme used by YouTube stuff
            $content = preg_replace('#href="\/(?!\/)#', 'href="http://ask.fm/',$content);
            $item->content = $content;
            $this->items[] = $item;
        }
    }

    public function getName(){
        return empty($this->request) ? $this->name : $this->request;
    }

    public function getURI(){
        return empty($this->request) ? $this->uri : 'http://ask.fm/'.urlencode($this->request);
    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }

}
