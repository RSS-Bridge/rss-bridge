<?php

declare(strict_types=1);

class TagesschauBridge extends FeedExpander
{
    const MAINTAINER = 'Schabi';
    const NAME = 'Tagesschau';
    const URI = 'https://www.tagesschau.de/';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'Shows full news from tagesschau.de.';
    const PARAMETERS = [[
        'feed' => [
            'name' => 'Feed',
            'type' => 'list',
            'title' => 'Select a feed',
            'values' => [
                'alle Meldungen von tagesschau.de' => 'alle_meldungen',
                'alle Meldungen der Startseite' => 'startseite',
                'Inland - alle Artikel' => 'inland',
                'Inland - Innenpolitik' => 'inland_innenpolitik',
                'Inland - Gesellschaft' => 'inland_gesellschaft',
                'Inland - Regional' => 'inland_regional',
                'Inland - Regional - Baden-Württemberg' => 'inland_regional_badenwuerttemberg',
                'Inland - Regional - Bayern' => 'inland_regional_bayern',
                'Inland - Regional - Berlin' => 'inland_regional_berlin',
                'Inland - Regional - Brandenburg' => 'inland_regional_brandenburg',
                'Inland - Regional - Bremen' => 'inland_regional_bremen',
                'Inland - Regional - Hamburg' => 'inland_regional_hamburg',
                'Inland - Regional - Hessen' => 'inland_regional_hessen',
                'Inland - Regional - Mecklenburg-Vorpommern' => 'inland_regional_mecklenburgvorpommern',
                'Inland - Regional - Niedersachsen' => 'inland_regional_niedersachsen',
                'Inland - Regional - Nordrhein-Westfalen' => 'inland_regional_nordrheinwestfalen',
                'Inland - Regional - Rheinland-Pfalz' => 'inland_regional_rheinlandpfalz',
                'Inland - Regional - Saarland' => 'inland_regional_saarland',
                'Inland - Regional - Sachsen' => 'inland_regional_sachsen',
                'Inland - Regional - Sachsen-Anhalt' => 'inland_regional_sachsenanhalt',
                'Inland - Regional - Schleswig-Holstein' => 'inland_regional_schleswigholstein',
                'Inland - Regional - Thüringen' => 'inland_regional_thueringen',
                'Ausland - alle Artikel' => 'ausland',
                'Ausland - Europa' => 'ausland_europa',
                'Ausland - Amerika' => 'ausland_amerika',
                'Ausland - Afrika' => 'ausland_afrika',
                'Ausland - Asien' => 'ausland_asien',
                'Ausland - Ozeanien' => 'ausland_ozeanien',
                'Wirtschaft - alle Artikel' => 'wirtschaft',
                'Wirtschaft - Finanzen' => 'wirtschaft_finanzen',
                'Wirtschaft - Unternehmen' => 'wirtschaft_unternehmen',
                'Wirtschaft - Verbraucher' => 'wirtschaft_verbraucher',
                'Wirtschaft - Technologie' => 'wirtschaft_technologie',
                'Wirtschaft - Weltwirtschaft' => 'wirtschaft_weltwirtschaft',
                'Wirtschaft - Konjunktur' => 'wirtschaft_konjunktur',
                'Wissen - alle Artikel' => 'wissen',
                'Wissen - Gesundheit' => 'wissen_gesundheit',
                'Wissen - Klima & Umwelt' => 'wissen_klima',
                'Wissen - Forschung' => 'wissen_forschung',
                'Wissen - Technologie' => 'wissen_technologie',
                'Faktenfinder' => 'faktenfinder',
                'Investigativ' => 'investigativ',
            ],
        ],
    ]];

    public function collectData()
    {
        $url = $this->getFeedUrl($this->getInput('feed'));
        $this->collectExpandableDatas($url);
    }

    protected function parseItem(array $item)
    {
        $uid = hash('sha256', $item['uri']);
        foreach ($this->items as $existing) {
            if (($existing['uid'] ?? null) === $uid) {
                return null;
            }
        }
        $item['uid'] = $uid;

        $titleImage = '';
        if (!empty($item['content'])) {
            $encoded = str_get_html($item['content']);
            if ($encoded) {
                $img = $encoded->find('img', 0);
                if ($img) {
                    $titleImage = $img->outertext;
                }
            }
        }

        $article = getSimpleHTMLDOM($item['uri']);
        if (!$article) {
            return $item;
        }

        $content = $article->find('article', 0);
        if (!$content) {
            return $item;
        }

        $headline = $content->find('.article-head__headline--text', 0);
        if ($headline) {
            $item['title'] = trim($headline->plaintext);
        }

        $authorMeta = $article->find('meta[name=author]', 0);
        if ($authorMeta) {
            $item['author'] = trim($authorMeta->content);
        }

        $metaline = $content->find('.metatextline', 0);
        if ($metaline) {
            $text = trim(str_replace(['Stand:', 'Uhr'], '', $metaline->plaintext));
            $text = trim(str_replace(['•', ' '], [' ', ' '], $text));
            $text = preg_replace('/\s+/', ' ', $text);
            $timestamp = \DateTime::createFromFormat('d.m.Y H:i', $text);
            if ($timestamp !== false) {
                $item['timestamp'] = $timestamp->getTimestamp();
            }
        }

        $html = $titleImage;
        $shorttext = $content->find('.article-head__shorttext', 0);
        if ($shorttext) {
            $html .= $shorttext->outertext;
        }
        foreach ($content->children() as $element) {
            $classes = explode(' ', $element->class ?? '');
            if ($element->tag === 'h2') {
                $html .= $element->outertext;
            } elseif (in_array('textabsatz', $classes, true)) {
                $html .= $element->outertext;
            } elseif (in_array('absatzbild', $classes, true)) {
                $html .= $element->outertext;
            }
        }
        $item['content'] = $html;

        return $item;
    }

    private function getFeedUrl(string $key): string
    {
        $map = [
            'alle_meldungen' => 'https://www.tagesschau.de/infoservices/alle-meldungen-100~rss2.xml',
            'startseite' => 'https://www.tagesschau.de/index~rss2.xml',
            'inland' => 'https://www.tagesschau.de/inland/index~rss2.xml',
            'inland_innenpolitik' => 'https://www.tagesschau.de/inland/innenpolitik/index~rss2.xml',
            'inland_gesellschaft' => 'https://www.tagesschau.de/inland/gesellschaft/index~rss2.xml',
            'inland_regional' => 'https://www.tagesschau.de/inland/regional/index~rss2.xml',
            'inland_regional_badenwuerttemberg' => 'https://www.tagesschau.de/inland/regional/badenwuerttemberg/index~rss2.xml',
            'inland_regional_bayern' => 'https://www.tagesschau.de/inland/regional/bayern/index~rss2.xml',
            'inland_regional_berlin' => 'https://www.tagesschau.de/inland/regional/berlin/index~rss2.xml',
            'inland_regional_brandenburg' => 'https://www.tagesschau.de/inland/regional/brandenburg/index~rss2.xml',
            'inland_regional_bremen' => 'https://www.tagesschau.de/inland/regional/bremen/index~rss2.xml',
            'inland_regional_hamburg' => 'https://www.tagesschau.de/inland/regional/hamburg/index~rss2.xml',
            'inland_regional_hessen' => 'https://www.tagesschau.de/inland/regional/hessen/index~rss2.xml',
            'inland_regional_mecklenburgvorpommern' => 'https://www.tagesschau.de/inland/regional/mecklenburgvorpommern/index~rss2.xml',
            'inland_regional_niedersachsen' => 'https://www.tagesschau.de/inland/regional/niedersachsen/index~rss2.xml',
            'inland_regional_nordrheinwestfalen' => 'https://www.tagesschau.de/inland/regional/nordrheinwestfalen/index~rss2.xml',
            'inland_regional_rheinlandpfalz' => 'https://www.tagesschau.de/inland/regional/rheinlandpfalz/index~rss2.xml',
            'inland_regional_saarland' => 'https://www.tagesschau.de/inland/regional/saarland/index~rss2.xml',
            'inland_regional_sachsen' => 'https://www.tagesschau.de/inland/regional/sachsen/index~rss2.xml',
            'inland_regional_sachsenanhalt' => 'https://www.tagesschau.de/inland/regional/sachsenanhalt/index~rss2.xml',
            'inland_regional_schleswigholstein' => 'https://www.tagesschau.de/inland/regional/schleswigholstein/index~rss2.xml',
            'inland_regional_thueringen' => 'https://www.tagesschau.de/inland/regional/thueringen/index~rss2.xml',
            'ausland' => 'https://www.tagesschau.de/ausland/index~rss2.xml',
            'ausland_europa' => 'https://www.tagesschau.de/ausland/europa/index~rss2.xml',
            'ausland_amerika' => 'https://www.tagesschau.de/ausland/amerika/index~rss2.xml',
            'ausland_afrika' => 'https://www.tagesschau.de/ausland/afrika/index~rss2.xml',
            'ausland_asien' => 'https://www.tagesschau.de/ausland/asien/index~rss2.xml',
            'ausland_ozeanien' => 'https://www.tagesschau.de/ausland/ozeanien/index~rss2.xml',
            'wirtschaft' => 'https://www.tagesschau.de/wirtschaft/index~rss2.xml',
            'wirtschaft_finanzen' => 'https://www.tagesschau.de/wirtschaft/finanzen/index~rss2.xml',
            'wirtschaft_unternehmen' => 'https://www.tagesschau.de/wirtschaft/unternehmen/index~rss2.xml',
            'wirtschaft_verbraucher' => 'https://www.tagesschau.de/wirtschaft/verbraucher/index~rss2.xml',
            'wirtschaft_technologie' => 'https://www.tagesschau.de/wirtschaft/technologie/index~rss2.xml',
            'wirtschaft_weltwirtschaft' => 'https://www.tagesschau.de/wirtschaft/weltwirtschaft/index~rss2.xml',
            'wirtschaft_konjunktur' => 'https://www.tagesschau.de/wirtschaft/konjunktur/index~rss2.xml',
            'wissen' => 'https://www.tagesschau.de/wissen/index~rss2.xml',
            'wissen_gesundheit' => 'https://www.tagesschau.de/wissen/gesundheit/index~rss2.xml',
            'wissen_klima' => 'https://www.tagesschau.de/wissen/klima/index~rss2.xml',
            'wissen_forschung' => 'https://www.tagesschau.de/wissen/forschung/index~rss2.xml',
            'wissen_technologie' => 'https://www.tagesschau.de/wissen/technologie/index~rss2.xml',
            'faktenfinder' => 'https://www.tagesschau.de/faktenfinder/index~rss2.xml',
            'investigativ' => 'https://www.tagesschau.de/investigativ/index~rss2.xml',
        ];

        return $map[$key];
    }
}
