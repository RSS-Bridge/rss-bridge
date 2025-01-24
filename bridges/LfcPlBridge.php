<?php

class LfcPlBridge extends BridgeAbstract
{
    const NAME = 'LFC (lfc.pl)';
    const DESCRIPTION = 'LFC.pl - największa polska strona o Liverpool FC';
    const URI = 'https://lfc.pl';
    const MAINTAINER = 'brtsos';
    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI . '/Archiwum/' . date('Y') . date('m'));

        $list = $dom->find('#page .list-vertical li');
        $list = array_reverse($list);
        $list = array_slice($list, 0, 10);

        foreach ($list as $li) {
            $link = $li->find('a', 0);
            $url = self::URI . $link->href;

            $articleDom = getSimpleHTMLDOM($url);

            $description = $this->getContent($articleDom);
            if (mb_strpos($description, 'Artykuł sponsorowany') !== false) {
                continue;
            }

            $image = '<img src="' . $this->getImage($articleDom) . '" alt="' . $link->plaintext . '" />';

            $content = $image . '</br>' . $description;

            $tagsToRemove = ['script', 'iframe', 'input', 'form'];
            $content = sanitize($content, $tagsToRemove);

            $footerArticle = $articleDom->find('.footer', 0)->find('.item', 0)->find('div', 1);
            $author = $footerArticle->find('a', 0)->plaintext;

            $dateTime = $footerArticle->find('div', 0)->plaintext;
            $date = DateTime::createFromFormat('d.m.Y H:i', $dateTime);
            $timestamp = $date->getTimestamp();
            $this->items[] = [
                'title' => $link->plaintext,
                'uri' => $url,
                'timestamp' => $timestamp,
                'content' => $content,
                'author' => $author,
            ];
        }
    }

    private function getContent($article)
    {
        $content = $article->find('.news-body', 0)->innertext;
        $commentsHtml = $article->find('#comments', 0);

        $comments = '';
        if ($commentsHtml) {
            $commentsDom = $commentsHtml->find('.comment');

            if (count($commentsDom) > 0) {
                $comments = '<h3>Komentarze:</h3>';
            }

            foreach ($commentsDom as $comment) {
                $header = $comment->find('.header', 0)->plaintext;
                $content = $comment->find('.content', 0)->plaintext;
                $comments .= $header . '<br />' . $content . '<br /><br />';
            }
        }

        return $content . '<br /> <br />' . $comments;
    }

    private function getImage($article): ?string
    {
        $imgElement = $article->find('#news .img', 0);
        if ($imgElement) {
            $style = $imgElement->style;

            if (preg_match('/background-image:\s*url\(([^)]+)\)/i', $style, $matches)) {
                return self::URI . trim($matches[1], "'\"");
            }

            return null;
        }

        return null;
    }
}