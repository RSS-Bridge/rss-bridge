<?php
class FuturaSciencesBridge extends BridgeAbstract {

    const MAINTAINER = 'ORelio';
    const NAME = 'Futura-Sciences Bridge';
    const URI = 'http://www.futura-sciences.com/';
    const DESCRIPTION = 'Returns the newest articles.';

    const PARAMETERS = array( array(
        'feed'=> array(
            'name'=>'Feed',
            'type'=>'list',
            'values'=>array(
                'Les flux multi-magazines'=>array(
                    'Les dernières actualités de Futura-Sciences'=>'actualites',
                    'Les dernières définitions de Futura-Sciences'=>'definitions',
                    'Les dernières photos de Futura-Sciences'=>'photos',
                    'Les dernières questions - réponses de Futura-Sciences'=>'questions-reponses',
                    'Les derniers dossiers de Futura-Sciences'=>'dossiers'
                ),
                'Les flux Services'=> array(
                    'Les cartes virtuelles de Futura-Sciences'=>'services/cartes-virtuelles',
                    'Les fonds d\'écran de Futura-Sciences'=>'services/fonds-ecran'
                ),
                'Les flux Santé'=>array(
                    'Les dernières actualités de Futura-Santé'=>'sante/actualites',
                    'Les dernières définitions de Futura-Santé'=>'sante/definitions',
                    'Les dernières questions-réponses de Futura-Santé'=>'sante/question-reponses',
                    'Les derniers dossiers de Futura-Santé'=>'sante/dossiers'
                ),
                'Les flux High-Tech'=>array(
                    'Les dernières actualités de Futura-High-Tech'=>'high-tech/actualites',
                    'Les dernières astuces de Futura-High-Tech'=>'high-tech/question-reponses',
                    'Les dernières définitions de Futura-High-Tech'=>'high-tech/definitions',
                    'Les derniers dossiers de Futura-High-Tech'=>'high-tech/dossiers'
                ),
                'Les flux Espace'=>array(
                    'Les dernières actualités de Futura-Espace'=>'espace/actualites',
                    'Les dernières définitions de Futura-Espace'=>'espace/definitions',
                    'Les dernières questions-réponses de Futura-Espace'=>'espace/question-reponses',
                    'Les derniers dossiers de Futura-Espace'=>'espace/dossiers'
                ),
                'Les flux Environnement'=>array(
                    'Les dernières actualités de Futura-Environnement'=>'environnement/actualites',
                    'Les dernières définitions de Futura-Environnement'=>'environnement/definitions',
                    'Les dernières questions-réponses de Futura-Environnement'=>'environnement/question-reponses',
                    'Les derniers dossiers de Futura-Environnement'=>'environnement/dossiers'
                ),
                'Les flux Maison'=>array(
                    'Les dernières actualités de Futura-Maison'=>'maison/actualites',
                    'Les dernières astuces de Futura-Maison'=>'maison/question-reponses',
                    'Les dernières définitions de Futura-Maison'=>'maison/definitions',
                    'Les derniers dossiers de Futura-Maison'=>'maison/dossiers'
                ),
                'Les flux Nature'=>array(
                    'Les dernières actualités de Futura-Nature'=>'nature/actualites',
                    'Les dernières définitions de Futura-Nature'=>'nature/definitions',
                    'Les dernières questions-réponses de Futura-Nature'=>'nature/question-reponses',
                    'Les derniers dossiers de Futura-Nature'=>'nature/dossiers'
                ),
                'Les flux Terre'=>array(
                    'Les dernières actualités de Futura-Terre'=>'terre/actualites',
                    'Les dernières définitions de Futura-Terre'=>'terre/definitions',
                    'Les dernières questions-réponses de Futura-Terre'=>'terre/question-reponses',
                    'Les derniers dossiers de Futura-Terre'=>'terre/dossiers'
                ),
                'Les flux Matière'=>array(
                    'Les dernières actualités de Futura-Matière'=>'matiere/actualites',
                    'Les dernières définitions de Futura-Matière'=>'matiere/definitions',
                    'Les dernières questions-réponses de Futura-Matière'=>'matiere/question-reponses',
                    'Les derniers dossiers de Futura-Matière'=>'matiere/dossiers'
                ),
                'Les flux Mathématiques'=>array(
                    'Les dernières actualités de Futura-Mathématiques'=>'mathematiques/actualites',
                    'Les derniers dossiers de Futura-Mathématiques'=>'mathematiques/dossiers'
                )
            )
        )
    ));

    public function collectData(){

        function StripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        function StripRecursiveHTMLSection($string, $tag_name, $tag_start) {
            $open_tag = '<'.$tag_name;
            $close_tag = '</'.$tag_name.'>';
            $close_tag_length = strlen($close_tag);
            if (strpos($tag_start, $open_tag) === 0) {
                while (strpos($string, $tag_start) !== false) {
                    $max_recursion = 100;
                    $section_to_remove = null;
                    $section_start = strpos($string, $tag_start);
                    $search_offset = $section_start;
                    do {
                        $max_recursion--;
                        $section_end = strpos($string, $close_tag, $search_offset);
                        $search_offset = $section_end + $close_tag_length;
                        $section_to_remove = substr($string, $section_start, $section_end - $section_start + $close_tag_length);
                        $open_tag_count = substr_count($section_to_remove, $open_tag);
                        $close_tag_count = substr_count($section_to_remove, $close_tag);
                    } while ($open_tag_count > $close_tag_count && $max_recursion > 0);
                    $string = str_replace($section_to_remove, '', $string);
                }
            }
            return $string;
        }

        // Extracts the author from an article or element
        function ExtractAuthor($article, $element){
            $article_author = $article->find('span.author', 0);
            if($article_author){
                $authorname = trim(str_replace(', Futura-Sciences', '', $article_author->plaintext));
                if(empty($authorname)){
                    $element_author = $element->find('author', 0);
                    if($element_author)
                        $authorname = StripCDATA($element_author->plaintext);
                    else
                        return '';
                }
                return $authorname;
            }
            return '';
        }

        $url = $this->getURI().'rss/'.$this->getInput('feed').'.xml';

        $html = $this->getSimpleHTMLDOM($url)
            or $this->returnServerError('Could not request Futura-Sciences: '.$url);
        $limit = 0;

        foreach($html->find('item') as $element) {
            if ($limit < 10) {
                $article_url = str_replace('#xtor=RSS-8', '', StripCDATA($element->find('guid', 0)->plaintext));
                $article = $this->getSimpleHTMLDOM($article_url) or $this->returnServerError('Could not request Futura-Sciences: '.$article_url);
                $contents = $article->find('div.content', 0)->innertext;

                foreach (array(
                    '<div class="clear',
                    '<div class="sharebar2',
                    '<div class="diaporamafullscreen"',
                    '<div style="margin-bottom:10px;" class="noprint"',
                    '<div class="ficheprevnext',
                    '<div class="bar noprint',
                    '<div class="toolbar noprint',
                    '<div class="addthis_toolbox',
                    '<div class="noprint',
                    '<div class="bg bglight border border-full noprint',
                    '<div class="httplogbar-wrapper noprint',
                    '<div id="forumcomments'
                ) as $div_start) {
                    $contents = StripRecursiveHTMLSection($contents , 'div', $div_start);
                }

                $contents = StripWithDelimiters($contents, '<hr ', '/>');
                $contents = StripWithDelimiters($contents, '<p class="content-date', '</p>');
                $contents = StripWithDelimiters($contents, '<h1 class="content-title', '</h1>');
                $contents = StripWithDelimiters($contents, 'fs:definition="', '"');
                $contents = StripWithDelimiters($contents, 'fs:xt:clicktype="', '"');
                $contents = StripWithDelimiters($contents, 'fs:xt:clickname="', '"');

                $item = array();
                $item['author'] = ExtractAuthor($article, $element);
                $item['uri'] = $article_url;
                $item['title'] = StripCDATA($element->find('title', 0)->innertext);
                $item['timestamp'] = strtotime(StripCDATA($element->find('pubDate', 0)->plaintext));
                $item['content'] = trim($contents);
                $this->items[] = $item;
                $limit++;
            }
        }

    }
}
