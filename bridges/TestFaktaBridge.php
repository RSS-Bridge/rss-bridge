<?PHP

class TestFaktaBridge extends BridgeAbstract
{
    const NAME = 'Testfakta';
    const URI = 'https://www.testfakta.se';
    const DESCRIPTION = 'Letest independent tests by Testfakta';
    const MAINTAINER = 'ajain-93';

    public function getIcon()
    {
        return self::URI . '/themes/testfakta/favicon.ico';
    }

    private function parseSwedishDates($dateString)
    {
        // Mapping of Swedish month names to English month names
        $months = [
            'Jan' => 'Jan',
            'Feb' => 'Feb',
            'Mar' => 'Mar',
            'Apr' => 'Apr',
            'Maj' => 'May',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Aug',
            'Sep' => 'Sep',
            'Okt' => 'Oct',
            'Nov' => 'Nov',
            'Dec' => 'Dec'
        ];

        // Replace Swedish month names with English
        $dateString = preg_replace_callback(
            '/\b(' . implode('|', array_keys($months)) . ')\b/',
            function ($matches) use ($months) {
                return $months[$matches[0]];
            },
            $dateString
        );

        // Create DateTime object
        $dateValue = DateTime::createFromFormat(
            'd M, Y',
            trim($dateString),
            new DateTimeZone('Europe/Stockholm')
        );
        if ($dateValue) {
            $dateValue->setTime(0, 0); // Set time to 00:00
            return $dateValue->getTimestamp();
        }

        return $dateValue ? $dateValue->getTimestamp() : false;
    }

    public function collectData()
    {
        $NEWSURL = self::URI . '/sv';
        $html = getSimpleHTMLDOMCached($NEWSURL, 18000);

        foreach ($html->find('.row-container') as $element) {
            // Debug::log($element);

            $title = $element->find('h2', 0)->plaintext;
            $category = trim($element->find('.red-label', 0)->plaintext);
            $url = self::URI . $element->find('a', 0)->getAttribute('href');
            $figure = $element->find('img', 0);
            $preamble = trim($element->find('.text', 0)->plaintext);

            $article_html = getSimpleHTMLDOMCached($url, 18000);
            $article_content = $article_html->find('div.content', 0);
            $article_text = $article_html->find('article', 0);

            $requestor = $article_html->find('div.uppdrag', 0)->plaintext;
            $author = trim($article_html->find('span.name', 0)->plaintext);
            $published = $this->parseSwedishDates(
                str_replace(
                    'Publicerad: ',
                    '',
                    trim($article_html->find('span.created', 0)->plaintext)
                )
            );

            $content = $figure . '<br/>';
            $content .= '<b>' . strtoupper($category) . '</b>  ' . $requestor . '<br/><br/>';
            $content .= '<b><i>' . $preamble . '</i></b><br/><br/>';
            $content .= $article_text;

            $this->items[] = [
                'uri' => $url,
                'title' => $title,
                'author' => $author,
                'timestamp' => $published,
                'content' => trim($content),
            ];
        }
    }
}
