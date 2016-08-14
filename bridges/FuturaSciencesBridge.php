<?php
class FuturaSciencesBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = 'ORelio';
        $this->name = 'Futura-Sciences Bridge';
        $this->uri = 'http://www.futura-sciences.com/';
        $this->description = 'Returns the newest articles.';
        $this->update = '2016-08-09';

        $this->parameters[] =
        '[
            {
                "name" : "Feed",
                "type" : "list",
                "identifier" : "feed",
                "values" :
                [
                    { "name" : "---- Select ----", "value" : "" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux multi-magazines", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Sciences", "value" : "actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura-Sciences", "value" : "definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières photos de Futura-Sciences", "value" : "photos" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières questions - réponses de Futura-Sciences", "value" : "questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Sciences", "value" : "dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Services", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les cartes virtuelles de Futura-Sciences", "value" : "services/cartes-virtuelles" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les fonds d\'écran de Futura-Sciences", "value" : "services/fonds-ecran" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Santé", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Santé", "value" : "sante/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura-Santé", "value" : "sante/definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières questions-réponses de Futura-Santé", "value" : "sante/questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Santé", "value" : "sante/dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux High-Tech", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura High-Tech", "value" : "high-tech/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières astuces de Futura High-Tech", "value" : "high-tech/questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura High-Tech", "value" : "high-tech/definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura High-Tech", "value" : "high-tech/dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Espace", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Espace", "value" : "espace/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura-Espace", "value" : "espace/definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières questions-réponses de Futura-Espace", "value" : "espace/questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Espace", "value" : "espace/dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Environnement", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Environnement", "value" : "environnement/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura-Environnement", "value" : "environnement/definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières questions - réponses de Futura-Environnement", "value" : "environnement/questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Environnement", "value" : "environnement/dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Maison", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Maison", "value" : "maison/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières astuces de Futura-Maison", "value" : "maison/questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura-Maison", "value" : "maison/definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Maison", "value" : "maison/dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Nature", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Nature", "value" : "nature/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura-Nature", "value" : "nature/definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières questions-réponses de Futura-Nature", "value" : "nature/questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Nature", "value" : "nature/dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Terre", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Terre", "value" : "terre/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura-Terre", "value" : "terre/definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières questions-réponses de Futura-Terre", "value" : "terre/questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Terre", "value" : "terre/dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Matière", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Matière", "value" : "matiere/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières définitions de Futura-Matière", "value" : "matiere/definitions" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières questions-réponses de Futura-Matière", "value" : "matiere/questions-reponses" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Matière", "value" : "matiere/dossiers" },

                    { "name" : "", "value" : "" },
                    { "name" : "Les flux Mathématiques", "value" : "" },

                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les dernières actualités de Futura-Mathématiques", "value" : "mathematiques/actualites" },
                    { "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Les derniers dossiers de Futura-Mathématiques", "value" : "mathematiques/dossiers" }
                ]
            }
        ]';

    }

    public function collectData(array $param) {

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

        if (empty($param['feed']))
            $this->returnError('Please select a feed to display.'.$url, 400);
        if ($param['feed'] !== preg_replace('/[^a-zA-Z-\/]+/', '', $param['feed']) || substr_count($param['feed'], '/') > 1 || strlen($param['feed'] > 64))
            $this->returnError('Invalid "feed" parameter.'.$url, 400);

        $url = $this->getURI().'rss/'.$param['feed'].'.xml';
        $html = $this->file_get_html($url) or $this->returnError('Could not request Futura-Sciences: '.$url, 500);
        $limit = 0;

        foreach($html->find('item') as $element) {
            if ($limit < 10) {
                $article_url = str_replace('#xtor=RSS-8', '', StripCDATA($element->find('guid', 0)->plaintext));
                $article = $this->file_get_html($article_url) or $this->returnError('Could not request Futura-Sciences: '.$article_url, 500);
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

                $item = new \Item();
                $item->author = ExtractAuthor($article, $element);
                $item->uri = $article_url;
                $item->title = StripCDATA($element->find('title', 0)->innertext);
                $item->timestamp = strtotime(StripCDATA($element->find('pubDate', 0)->plaintext));
                $item->content = trim($contents);
                $this->items[] = $item;
                $limit++;
            }
        }

    }
}
