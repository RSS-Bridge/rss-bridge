<?php
class AskfmBridge extends BridgeAbstract{

    public $maintainer = "az5he6ch";
    public $name = "Ask.fm Answers";
    public $uri = "http://ask.fm/";
    public $description = "Returns answers from an Ask.fm user";
    public $parameters = array(
        'Ask.fm username'=>array(
            'u'=>array(
                'name'=>'Username',
                'required'=>true
            )
        )
    );

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->getURI())
            or $this->returnServerError('Requested username can\'t be found.');

        foreach($html->find('div.streamItem-answer') as $element) {
            $item = array();
            $item['uri'] = $this->uri.$element->find('a.streamItemsAge',0)->href;
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
            $content = preg_replace('#href="\/(?!\/)#', 'href="'.$this->uri,$content);
            $item['content'] = $content;
            $this->items[] = $item;
        }
    }

    public function getName(){
        return $this->name.' : '.$this->getInput('u');
    }

    public function getURI(){
        return $this->uri.urlencode($this->getInput('u')).'/answers/more?page=0';
    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }

}
