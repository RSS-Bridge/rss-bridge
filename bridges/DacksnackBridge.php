<?PHP

class DacksnackBridge extends BridgeAbstract
{
    const NAME = 'Däcksnack';
    const URI = 'https://www.tidningendacksnack.se';
    const DESCRIPTION = 'Latest news by the magazine Däcksnack';
    const MAINTAINER = 'ajain-93';

    public function getIcon()
    {
        return self::URI . '/upload/favicon/2591047722.png';
    }

    private function parseSwedishDates($dateString)
    {
        // Mapping of Swedish month names to English month names
        $monthNames = [
            'januari' => '01',
            'februari' => '02',
            'mars' => '03',
            'april' => '04',
            'maj' => '05',
            'juni' => '06',
            'juli' => '07',
            'augusti' => '08',
            'september' => '09',
            'oktober' => '10',
            'november' => '11',
            'december' => '12'
        ];

        // Split the date string into parts
        list($day, $monthName, $year) = explode(' ', $dateString);

        // Convert month name to month number
        $month = $monthNames[$monthName];

        // Format to a string recognizable by DateTime
        $formattedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);

        // Create a DateTime object
        $dateValue = new DateTime($formattedDate);

        if ($dateValue) {
            $dateValue->setTime(0, 0); // Set time to 00:00
            return $dateValue->getTimestamp();
        }

        return $dateValue ? $dateValue->getTimestamp() : false;
    }

    public function collectData()
    {
        $NEWSURL = self::URI;
        $html = getSimpleHTMLDOMCached($NEWSURL, 18000) or
            returnServerError('Could not request: ' . $NEWSURL);

        foreach ($html->find('a.main-news-item') as $element) {
            // Debug::log($element);

            $title = trim($element->find('h2', 0)->plaintext);
            $category = trim($element->find('.category-tag', 0)->plaintext);
            $url = self::URI . $element->getAttribute('href');
            $published = $this->parseSwedishDates(trim($element->find('.published', 0)->plaintext));

            $article_html = getSimpleHTMLDOMCached($url, 18000) or
                returnServerError('Could not request: ' . $url);
            $article_content = $article_html->find('#ctl00_ContentPlaceHolder1_NewsArticleVeiw_pnlArticle', 0);

            $figure = self::URI . $article_content->find('img.news-image', 0)->getAttribute('src');
            $figure_caption = $article_content->find('.image-description', 0)->plaintext;
            $author = $article_content->find('span.main-article-author', 0)->plaintext;
            $preamble = $article_content->find('h4.main-article-ingress', 0)->plaintext;

            $article_text = '';
            foreach ($article_content->find('div') as $div) {
                if (!$div->hasAttribute('class')) {
                    $article_text = $div;
                }
            }

            // Use a regular expression to extract the name
            if (preg_match('/Text:\s*(.*?)\s*Foto:/', $author, $matches)) {
                $author = $matches[1]; // This will contain 'Jonna Jansson'
            }

            $content = '<b> [' . $category . '] <i>' . $preamble . '</i></b><br/><br/>';
            $content .= '<figure>';
            $content .= '<img src=' . $figure . '>';
            $content .= '<figcaption>' . $figure_caption . '</figcaption>';
            $content .= '</figure>';
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
