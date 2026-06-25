<?php

declare(strict_types=1);

class PanoramaBridge extends BridgeAbstract {
    const MAINTAINER = 'LordArrin';
    const NAME = 'IA Panorama';
    const URI = 'https://panorama.pub';
    const DESCRIPTION = 'News feed of the Russian satirical information agency "Panorama"';
    const CACHE_TIMEOUT = 3600;

    public function collectData(): void {
        $dates = $this->getDatesToFetch();
        $processedUris = [];

        foreach ($dates as $date) {
            $url = self::URI . '/news/' . $date;
            
            $htmlContent = $this->fetchPageContent($url);
            if ($htmlContent === null) {
                continue;
            }
            
            $html = str_get_html($htmlContent);
            $html = defaultLinkTo($html, self::URI);
            $cards = $html->find('a.flex-col');

            foreach ($cards as $card) {
                $uri = $card->href;
                $path = parse_url($uri, PHP_URL_PATH);
                
                if (!$this->isValidNewsUri($path, $processedUris)) {
                    continue;
                }
                
                $processedUris[] = $uri;
                $item = $this->processNewsCard($card, $uri);
                
                if ($item !== null) {
                    $this->items[] = $item;
                }
                
                usleep(800000); // 800ms delay between requests
            }
        }
    }

    private function fetchPageContent(string $url): ?string {
        try {
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'
            ];
            return getContents($url, $headers);
        } catch (Exception $e) {
            return null;
        }
    }

    private function getDatesToFetch(): array {
        $tzString = date_default_timezone_get();
        $timezone = new DateTimeZone($tzString);
        
        $today = new DateTime('now', $timezone);
        $yesterday = new DateTime('now', $timezone);
        $yesterday->modify('-1 day');
        
        return [
            $today->format('d-m-Y'),
            $yesterday->format('d-m-Y')
        ];
    }

    private function isValidNewsUri(?string $path, array $processedUris): bool {
        if ($path === null) {
            return false;
        }
        
        if (preg_match('/^\/news\/\d{2}-\d{2}-\d{4}$/', $path)) {
            return false;
        }
        
        if ($path === '/news' || $path === '/news/') {
            return false;
        }
        
        if (in_array($path, $processedUris)) {
            return false;
        }
        
        return true;
    }

    private function processNewsCard($card, string $uri): ?array {
        $previewTitle = $this->extractPreviewTitle($card);
        $previewImage = $this->extractPreviewImage($card);
        
        $articleContent = $this->fetchPageContent($uri);
        if ($articleContent === null) {
            return null;
        }
        
        $articleHTML = str_get_html($articleContent);
        $articleHTML = defaultLinkTo($articleHTML, self::URI);
        
        $item = [
            'uri' => $uri,
            'uid' => $uri,
            'title' => $this->extractTitle($articleHTML, $previewTitle),
            'timestamp' => $this->extractTimestamp($articleHTML),
            'author' => $this->extractAuthor($articleHTML),
            'content' => $this->buildFinalContent(
                $this->extractImage($articleHTML, $previewImage),
                $this->extractContent($articleHTML, $previewTitle),
                $this->extractTitle($articleHTML, $previewTitle)
            )
        ];
        
        return $item;
    }

    private function extractPreviewTitle($card): string {
        $titleDiv = $card->find('div.font-semibold', 0);
        return $titleDiv ? trim($titleDiv->plaintext) : '';
    }

    private function extractPreviewImage($card): string {
        $imgTag = $card->find('img', 0);
        if (!$imgTag) {
            return '';
        }
        
        $src = $imgTag->src;
        if (strpos($src, '//') === 0) {
            return 'https:' . $src;
        }
        
        return $src;
    }

    private function extractTitle($articleHTML, string $fallbackTitle): string {
        $h1 = $articleHTML->find('h1[itemprop=headline]', 0);
        if ($h1) {
            return trim($h1->plaintext);
        }
        
        $ogTitle = $articleHTML->find('meta[property="og:title"]', 0);
        return $ogTitle ? trim($ogTitle->content) : $fallbackTitle;
    }

    private function extractTimestamp($articleHTML): int {
        $publishedTime = $articleHTML->find('meta[property="article:published_time"]', 0);
        return $publishedTime ? strtotime($publishedTime->content) : time();
    }

    private function extractAuthor($articleHTML): string {
        $authorTag = $articleHTML->find('meta[property="article:author"]', 0);
        return $authorTag ? $authorTag->content : 'IA Panorama';
    }

    private function extractImage($articleHTML, string $fallbackImage): string {
        $ogImage = $articleHTML->find('meta[property="og:image"]', 0);
        $imageUrl = $ogImage ? trim($ogImage->content) : $fallbackImage;
        
        if (strpos($imageUrl, '//') === 0) {
            return 'https:' . $imageUrl;
        }
        
        return $imageUrl;
    }

    private function extractContent($articleHTML, string $fallbackDescription): string {
        $contentElem = $articleHTML->find('div[itemprop=articleBody]', 0);
        if (!$contentElem) {
            $contentElem = $articleHTML->find('.entry-contents', 0);
        }

        if ($contentElem) {
            $this->cleanContent($contentElem);
            return $contentElem->innertext;
        }
        
        $ogDesc = $articleHTML->find('meta[property="og:description"]', 0);
        $description = $ogDesc ? trim($ogDesc->content) : $fallbackDescription;
        return '<p><em>' . htmlspecialchars($description) . '</em></p>';
    }

    private function cleanContent($contentElem): void {
        $junkSelectors = [
            'script', 
            'style', 
            'div[id*=yandex_rtb]',
            '.sharethis-inline-share-buttons',
            '.alert'
        ];
        
        foreach($contentElem->find(implode(',', $junkSelectors)) as $junk) {
            $junk->outertext = '';
        }
    }

    private function buildFinalContent(string $imageUrl, string $content, string $title): string {
        $finalContent = '';
        
        if (!empty($imageUrl)) {
            $finalContent .= '<figure><img src="' . $imageUrl . '" alt="' . htmlspecialchars($title) . '" /></figure><br/>';
        }
        
        $finalContent .= $content;
        
        return $finalContent;
    }
}