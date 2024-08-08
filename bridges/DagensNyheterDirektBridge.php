<?PHP

class DagensNyheterDirektBridge extends BridgeAbstract
{
    const NAME          = 'Dagens Nyheter Direkt';
    const URI           = 'https://www.dn.se/direkt/';
    const BASEURL       = 'https://www.dn.se';
    const DESCRIPTION   = 'Latest news summarised by Dagens Nyheter';
    const MAINTAINER    = 'ajain-93';
    const LIMIT         = 20;

    public function getIcon()
    {
        return 'https://cdn.dn-static.se/images/favicon__c2dd3284b46ffdf4d520536e526065fa8.svg';
    }

    public function collectData()
    {
        $NEWSURL = self::BASEURL . '/ajax/direkt/';

        $html = getSimpleHTMLDOM($NEWSURL) or
            returnServerError('Could not request: ' . $NEWSURL);

        foreach ($html->find('article') as $element) {
            $link = $element->find('button', 0)->getAttribute('data-link');
            $datetime = $element->getAttribute('data-publication-time');
            $url = self::BASEURL . $link;
            $title = $element->find('h2', 0)->plaintext;
            $author = $element->find('div.ds-byline__titles', 0)->plaintext;

            $article_content = $element->find('div.direkt-post__content', 0);
            $article_html = '';

            $figure = $element->find('figure', 0);

            if ($figure) {
                $article_html = $figure->find('img', 0) . '<p><i>' . $figure->find('figcaption', 0) . '</i></p>';
            }

            foreach ($article_content->find('p') as $p) {
                $article_html = $article_html . $p;
            }

            $this->items[] = [
                'uri' => $url,
                'title' => $title,
                'author' => trim($author),
                'timestamp' => $datetime,
                'content' => trim($article_html),
            ];

            if (count($this->items) > self::LIMIT) {
                break;
            }
        }
    }
}
