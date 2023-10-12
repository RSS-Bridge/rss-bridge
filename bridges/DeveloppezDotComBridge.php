<?php

class DeveloppezDotComBridge extends FeedExpander
{
    const MAINTAINER = 'Binnette';
    const NAME = 'Developpez.com Actus (FR)';
    const URI = 'https://www.developpez.com/';
    const DOMAIN = '.developpez.com/';
    const RSS_URL = 'index/rss';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns complete posts from developpez.com';
    // Encodings used by Developpez.com in their articles body
    const ENCONDINGS = ['Windows-1252', 'UTF-8'];
    const PARAMETERS = [
        [
            'limit' => [
                'name' => 'Max items',
                'type' => 'number',
                'defaultValue' => 5,
            ],
            // list of the differents RSS availables
            'domain' => [
                'type' => 'list',
                'name' => 'Domaine',
                'title' => 'Chosissez un sous-domaine',
                'values' => [
                    '= Domaine principal =' => 'www',
                    '4d' => '4d',
                    'abbyy' => 'abbyy',
                    'access' => 'access',
                    'agile' => 'agile',
                    'ajax' => 'ajax',
                    'algo' => 'algo',
                    'alm' => 'alm',
                    'android' => 'android',
                    'apache' => 'apache',
                    'applications' => 'applications',
                    'arduino' => 'arduino',
                    'asm' => 'asm',
                    'asp' => 'asp',
                    'aspose' => 'aspose',
                    'bacasable' => 'bacasable',
                    'big-data' => 'big-data',
                    'bpm' => 'bpm',
                    'bsd' => 'bsd',
                    'business-intelligence' => 'business-intelligence',
                    'c' => 'c',
                    'cloud-computing' => 'cloud-computing',
                    'club' => 'club',
                    'cms' => 'cms',
                    'cpp' => 'cpp',
                    'crm' => 'crm',
                    'css' => 'css',
                    'd' => 'd',
                    'dart' => 'dart',
                    'data-science' => 'data-science',
                    'db2' => 'db2',
                    'delphi' => 'delphi',
                    'dotnet' => 'dotnet',
                    'droit' => 'droit',
                    'eclipse' => 'eclipse',
                    'edi' => 'edi',
                    'embarque' => 'embarque',
                    'emploi' => 'emploi',
                    'etudes' => 'etudes',
                    'excel' => 'excel',
                    'firebird' => 'firebird',
                    'flash' => 'flash',
                    'go' => 'go',
                    'green-it' => 'green-it',
                    'gtk' => 'gtk',
                    'hardware' => 'hardware',
                    'hpc' => 'hpc',
                    'humour' => 'humour',
                    'ibmcloud' => 'ibmcloud',
                    'intelligence-artificielle' => 'intelligence-artificielle',
                    'interbase' => 'interbase',
                    'ios' => 'ios',
                    'java' => 'java',
                    'javascript' => 'javascript',
                    'javaweb' => 'javaweb',
                    'jetbrains' => 'jetbrains',
                    'jeux' => 'jeux',
                    'kotlin' => 'kotlin',
                    'labview' => 'labview',
                    'laravel' => 'laravel',
                    'latex' => 'latex',
                    'lazarus' => 'lazarus',
                    'linux' => 'linux',
                    'mac' => 'mac',
                    'matlab' => 'matlab',
                    'megaoffice' => 'megaoffice',
                    'merise' => 'merise',
                    'microsoft' => 'microsoft',
                    'mobiles' => 'mobiles',
                    'mongodb' => 'mongodb',
                    'mysql' => 'mysql',
                    'netbeans' => 'netbeans',
                    'nodejs' => 'nodejs',
                    'nosql' => 'nosql',
                    'objective-c' => 'objective-c',
                    'office' => 'office',
                    'open-source' => 'open-source',
                    'openoffice-libreoffice' => 'openoffice-libreoffice',
                    'oracle' => 'oracle',
                    'outlook' => 'outlook',
                    'pascal' => 'pascal',
                    'perl' => 'perl',
                    'php' => 'php',
                    'portail-emploi' => 'portail-emploi',
                    'portail-projets' => 'portail-projets',
                    'postgresql' => 'postgresql',
                    'powerpoint' => 'powerpoint',
                    'preprod-emploi' => 'preprod-emploi',
                    'programmation' => 'programmation',
                    'project' => 'project',
                    'purebasic' => 'purebasic',
                    'pyqt' => 'pyqt',
                    'python' => 'python',
                    'qt-creator' => 'qt-creator',
                    'qt' => 'qt',
                    'r' => 'r',
                    'raspberry-pi' => 'raspberry-pi',
                    'reseau' => 'reseau',
                    'ruby' => 'ruby',
                    'rust' => 'rust',
                    'sap' => 'sap',
                    'sas' => 'sas',
                    'scilab' => 'scilab',
                    'securite' => 'securite',
                    'sgbd' => 'sgbd',
                    'sharepoint' => 'sharepoint',
                    'solutions-entreprise' => 'solutions-entreprise',
                    'spring' => 'spring',
                    'sqlserver' => 'sqlserver',
                    'stages' => 'stages',
                    'supervision' => 'supervision',
                    'swift' => 'swift',
                    'sybase' => 'sybase',
                    'symfony' => 'symfony',
                    'systeme' => 'systeme',
                    'talend' => 'talend',
                    'typescript' => 'typescript',
                    'uml' => 'uml',
                    'unix' => 'unix',
                    'vb' => 'vb',
                    'vba' => 'vba',
                    'virtualisation' => 'virtualisation',
                    'visualstudio' => 'visualstudio',
                    'web-semantique' => 'web-semantique',
                    'web' => 'web',
                    'webmarketing' => 'webmarketing',
                    'wind' => 'wind',
                    'windows-azure' => 'windows-azure',
                    'windows' => 'windows',
                    'windowsphone' => 'windowsphone',
                    'word' => 'word',
                    'xhtml' => 'xhtml',
                    'xml' => 'xml',
                    'zend-framework' => 'zend-framework'
                ],
            ]
        ]
    ];

    /**
     * Grabs the RSS item from Developpez.com
     */
    public function collectData()
    {
        $url = $this->getRssUrl();
        $this->collectExpandableDatas($url, 20);
    }

    /**
     * Parse the content of every RSS item. And will try to get the full article
     * pointed by the item URL intead of the default abstract.
     */
    protected function parseItem(array $item)
    {
        if (count($this->items) >= $this->getInput('limit')) {
            return null;
        }

        // There is a bug in Developpez RSS, coma are writtent as '~?' in the
        // title, so I have to fix it manually
        $item['title'] = $this->fixComaInTitle($item['title']);

        // We get the content of the full article behind the RSS item URL
        $articleHTMLContent = getSimpleHTMLDOMCached($item['uri']);

        // Here we call our custom parser
        $fullText = $this->extractFullText($articleHTMLContent);
        if (!is_null($fullText)) {
            // if we manage to parse the page behind the url of the RSS item
            // then we set it as the new content. Otherwise we keep the default
            // content to avoid RSS Bridge to return an empty item
            $item['content'] = $fullText;
        }

        // Now we will attach video url in item
        $videosUrl = $this->getAllVideoUrl($articleHTMLContent);
        if (!empty($videosUrl)) {
            $item['enclosures'] = array_merge($item['enclosures'], $videosUrl);
        }

        // Now we can look for the blog writer/creator
        $author = $articleHTMLContent->find('[itemprop="creator"]', 0);
        if (!empty($author)) {
            $item['author'] = $author->outertext;
        }

        return $item;
    }

    /**
     * Return the RSS url for selected domain
     */
    private function getRssUrl()
    {
        $domain = $this->getInput('domain');
        if (!empty($domain)) {
            return 'https://' . $domain . self::DOMAIN . self::RSS_URL;
        }

        return self::URI . self::RSS_URL;
    }

    /**
     * Replace '~?' by a proper coma ','
     */
    private function fixComaInTitle($txt)
    {
        return str_replace('~?', ',', $txt);
    }

    /**
     * Return the full article pointed by the url in the RSS item
     * Since Developpez.com only provides a short abstract of the article, we
     * use the url to retrieve the complete article and return it as the content
     */
    private function extractFullText($articleHTMLContent)
    {
        // All blog entry contains a div with the class 'content'. This div
        // contains the complete blog article. But the RSS can also return
        // announcement and not a blog article. So the next if, should take
        // care of the "non blog" entry
        $divArticleEntry = $articleHTMLContent->find('div.content', 0);
        if (is_null($divArticleEntry)) {
            // Didn't find the div with class content. It is probably not a blog
            // entry. It is probably just an announcement for an ebook, a PDF,
            // etc. So we can use the default RSS item content.
            return null;
        }

        // The following code is a bit hacky, but I really manage to get the
        // full content of articles without any encoding issues. What is very
        // weird and ugly in Developpez.com is the fact the some paragraphs of
        // the article will be encoded as UTF-8 and some other paragraphs will
        // be encoded as Windows-1252. So we can NOT decode the full article
        // with only one encoding. We have to check every paragraph and
        // determine its encoding

        // This contains all the 'paragraphs' of the article. It includes the
        // pictures, the text and the links at the bottom of the article
        $paragraphs = $divArticleEntry->nodes;
        // This will store the complete decoded content
        $fullText = '';

        // For each paragraph, we will identify the encoding, then decode it
        // and finally store the decoded content in $text
        foreach ($paragraphs as $paragraph) {
            // We have to recreate a new DOM document from the current node
            // otherwise the find function will look in the complet article and
            // not only in the current paragraph. This is an ugly behavior of
            // the library Simple HTML DOM Parser...
            $html = str_get_html($paragraph->outertext);
            $fullText .= $this->decodeParagraph($html);
        }

        // Finally we return the full 'well' enconded content of the article
        return $fullText;
    }

    /**
     *
     */
    private function decodeParagraph($p)
    {
        // First we check if this paragraph is a video
        $videoUrl = $this->getVideoUrl($p);
        if (!empty($videoUrl)) {
            // If this is a video, we just return a link to the video
            // &#128250; => 🎞️
            return  '<p>
						<b>&#128250; <a href="' . $videoUrl . '">Voir la vidéo</a></b>
					</p>';
        }

        // We take outertext to get the complete paragraph not only the text
        // inside it. That way we still graph block <img> and so on.
        $pTxt = $p->outertext;
        // This will store the decoded text if we manage to decode it
        $decodedTxt = '';

        // This is the only way to properly decode each paragraph. I tried
        // many stuffs but this is the only working way I found.
        foreach (self::ENCONDINGS as $enc) {
            // We check the encoding of the current paragraph
            if (mb_check_encoding($pTxt, $enc)) {
                // If the encoding is well recognized, we can convert from
                // this encoding to UTF-8
                $decodedTxt = iconv($enc, 'UTF-8', $pTxt);
            }
        }

        // We should not trim the strings to avoid the <a> to be glued to the
        // text like: the software<a href="...">started</a>to...
        if (!empty($decodedTxt)) {
            // We manage to decode the text, so we take the decoded version
            return $this->formatParagraph($decodedTxt);
        } else {
            // Otherwise we take the non decoded version and hope it will
            // be displayed not too ugly in the fulltext content
            return $this->formatParagraph($pTxt);
        }
    }

    /**
     * Return true in $txt is a HTML tag and not plain text
     */
    private function isHtmlTagNotTxt($txt)
    {
        if ($txt === '') {
            return false;
        }
        $html = str_get_html($txt);
        return $html && $html->root && count($html->root->children) > 0;
    }

    /**
     * Will add a space before paragraph when needed
     */
    private function formatParagraph($txt)
    {
        // If the paragraph is an html tag, we add a space before
        if ($this->isHtmlTagNotTxt($txt)) {
            // the first element is an html tag and not a text, so we can add a
            // space before it
            return ' ' . $txt;
        }
        // If the text start with word (not punctation), we had a space
        $pattern = '/^\w/';
        if (preg_match($pattern, $txt)) {
            return ' ' . $txt;
        }
        return $txt;
    }

    /**
     * Retrieve all video url in the article
     */
    private function getAllVideoUrl($item)
    {
        // Array of video url
        $url = [];

        // Developpez use a div with the class video-container
        $divsVideo = $item->find('div.video-container');
        if (empty($divsVideo)) {
            return $url;
        }

        // get the url of the video
        foreach ($divsVideo as $div) {
            $html = str_get_html($div->outertext);
            $url[] = $this->getVideoUrl($html);
        }

        return $url;
    }

    /**
     * Retrieve URL video. We have to check for the src of an iframe
     * Work for Youtube. Will have to test for other video platform
     */
    private function getVideoUrl($p)
    {
        $divVideo = $p->find('div.video-container', 0);
        if (empty($divVideo)) {
            return null;
        }
        $iframe = $divVideo->find('iframe', 0);
        if (empty($iframe)) {
            return null;
        }
        $src = trim($iframe->getAttribute('src'));
        if (empty($src)) {
            return null;
        }
        if (str_starts_with($src, '//')) {
            $src = 'https:' . $src;
        }
        return $src;
    }
}
