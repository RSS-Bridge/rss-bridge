<?php

class LegifranceJOBridge extends BridgeAbstract
{
    const MAINTAINER = 'Pierre Mazière';
    const NAME = 'Journal Officiel de la République Française';
    // This uri returns a snippet of js. Should probably be https://www.legifrance.gouv.fr/jorf/jo/
    const URI = 'https://www.legifrance.gouv.fr/affichJO.do';
    const DESCRIPTION = 'Returns the laws and decrees officially registered daily in France';

    const PARAMETERS = [];

    private $author;
    private $timestamp;
    private $uri;

    private function extractItem($section, $subsection = null, $origin = null)
    {
        $item = [];
        $item['author'] = $this->author;
        $item['timestamp'] = $this->timestamp;
        $item['uri'] = $this->uri . '#' . count($this->items);
        $item['title'] = $section->plaintext;

        if (!is_null($origin)) {
            $item['title'] = '[ ' . $item['title'] . ' / ' . $subsection->plaintext . ' ] ' . $origin->plaintext;
            $data = $origin;
        } elseif (!is_null($subsection)) {
            $item['title'] = '[ ' . $item['title'] . ' ] ' . $subsection->plaintext;
            $data = $subsection;
        } else {
            $data = $section;
        }

        $item['content'] = '';
        foreach ($data->nextSibling()->find('a') as $content) {
            $text = $content->plaintext;
            $href = $content->nextSibling()->getAttribute('resource');
            $item['content'] .= '<p><a href="' . $href . '">' . $text . '</a></p>';
        }
        return $item;
    }

    public function getIcon()
    {
        return 'https://www.legifrance.gouv.fr/img/favicon.ico';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI)
            or $this->returnServer('Unable to download ' . self::URI);

        $this->author = trim($html->find('h2.titleJO', 0)->plaintext);
        $uri = $html->find('h2.titleELI', 0)->plaintext;
        $this->uri = trim(substr($uri, strpos($uri, 'https')));
        $this->timestamp = strtotime(substr($this->uri, strpos($this->uri, 'eli/jo/') + strlen('eli/jo/'), -5));

        foreach ($html->find('h3') as $section) {
            $subsections = $section->nextSibling()->find('h4');
            foreach ($subsections as $subsection) {
                $origins = $subsection->nextSibling()->find('h5');
                foreach ($origins as $origin) {
                    $this->items[] = $this->extractItem($section, $subsection, $origin);
                }
                if (!empty($origins)) {
                    continue;
                }
                $this->items[] = $this->extractItem($section, $subsection);
            }
            if (!empty($subsections)) {
                continue;
            }
            $this->items[] = $this->extractItem($section);
        }
    }
}
