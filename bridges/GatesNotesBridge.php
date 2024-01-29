<?php

class GatesNotesBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting';
    const NAME = 'Gates Notes';
    const URI = 'https://www.gatesnotes.com';
    const DESCRIPTION = 'Returns the newest articles.';
    const CACHE_TIMEOUT = 21600; // 6h

    public function collectData()
    {
        $params = [
            'validYearsString' => 'all',
            'setNumber' => '0',
            'sortByVideo' => 'all',
            'sortByTopic' => 'all'
        ];
        $api_endpoint = '/api/TGNWebAPI/Get_Filtered_Article_Set?';
        $apiUrl = self::URI . $api_endpoint . http_build_query($params);

        $rawContent = getContents($apiUrl);
        $cleanedContent = trim($rawContent, '"');
        $cleanedContent = str_replace('\r\n', "\n", $cleanedContent);
        $cleanedContent = stripslashes($cleanedContent);

        $json = Json::decode($cleanedContent, false);
        if (is_string($json)) {
            throw new \Exception('wtf? ' . $json);
        }

        foreach ($json as $article) {
            $item = [];

            $articleUri = self::URI . '/' . $article->{'_system_'}->name;

            $item['uri'] = $articleUri;
            $item['title'] = $article->headline;
            $item['content'] = self::getItemContent($articleUri);
            $item['timestamp'] = strtotime($article->date);

            $this->items[] = $item;
        }
    }

    protected function getItemContent($articleUri)
    {
        // We need to change the headers as the normal desktop website
        // use canvas-based image carousels for some pictures
        $headers = [
            'User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ];
        $article_html = getSimpleHTMLDOMCached($articleUri, 86400, $headers);

        $content = '';
        if (!$article_html) {
            $content .= '<p><em>Could not request ' . $this->getName() . ': ' . $articleUri . '</em></p>';
            return $content;
        }
        $article_html = defaultLinkTo($article_html, $this->getURI());

        $top_description = '<p>' . $article_html->find('div.article_top_description', 0)->innertext . '</p>';
        $heroImage = $article_html->find('img.article_top_DMT_Image', 0);
        if ($heroImage) {
            $hero_image = '<img src=' . $heroImage->getAttribute('data-src') . '>';
        }
        $article_body = $article_html->find('div.TGN_Article_ReadTimeSection', 0);

        // Remove the menu bar on some articles (PDF download etc.)
        foreach ($article_body->find('.TGN_MenuHolder') as $found) {
            $found->remove();
        }

        // For the carousels pictures, we still to remove the lazy-loading and force the real picture
        foreach ($article_body->find('canvas') as $found) {
            $found->remove();
        }
        foreach ($article_body->find('.TGN_PE_C_Img') as $found) {
            $found->setAttribute('src', $found->getAttribute('data-src'));
        }

        // Convert iframe of Youtube videos to link
        foreach ($article_body->find('iframe') as $found) {
            $iframeUrl = $found->getAttribute('src');

            if ($iframeUrl) {
                $text = 'Embedded Youtube video, click here to watch on Youtube.com';
                $found->outertext = '<p><a href="' . $iframeUrl . '">' . $text . '</a></p>';
            }
        }

        // Remove <link> CSS ressources
        foreach ($article_body->find('link') as $found) {
            $linkedRessourceUrl = $found->getAttribute('href');

            if (str_ends_with($linkedRessourceUrl, '.css')) {
                $found->outertext = '';
            }
        }
        $article_body = sanitize($article_body->innertext);

        $content = $top_description . ($hero_image ?? '') . $article_body;

        return $content;
    }
}
