<?php
class AskfmBridge extends BridgeAbstract{

    const MAINTAINER = "az5he6ch";
    const NAME = "Ask.fm Answers";
    const URI = "http://ask.fm/";
    const CACHE_TIMEOUT = 300; //5 min
    const DESCRIPTION = "Returns answers from an Ask.fm user";
    const PARAMETERS = array(
        'Ask.fm username'=>array(
            'u'=>array(
                'name'=>'Username',
                'required'=>true
            )
        )
    );

    public function collectData(){
        $html = getSimpleHTMLDOM($this->getURI())
            or returnServerError('Requested username can\'t be found.');

        foreach($html->find('div.streamItem-answer') as $element) {
            $item = array();
            $item['uri'] = self::URI.$element->find('a.streamItemsAge',0)->href;
            $question = trim($element->find('h1.streamItemContent-question',0)->innertext);
            $item['title'] = trim(htmlspecialchars_decode($element->find('h1.streamItemContent-question',0)->plaintext, ENT_QUOTES));
            $answer = trim($element->find('p.streamItemContent-answer',0)->innertext);
            #$item['update'] = $element->find('a.streamitemsage',0)->data-hint; // Doesn't work, DOM parser doesn't seem to like data-hint, dunno why
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
            $content = preg_replace('#href="\/(?!\/)#', 'href="'.self::URI,$content);
            $item['content'] = $content;
            $this->items[] = $item;
        }
    }

    public function getName(){
        return self::NAME.' : '.$this->getInput('u');
    }

    public function getURI(){
        return self::URI.urlencode($this->getInput('u')).'/answers/more?page=0';
    }
}
