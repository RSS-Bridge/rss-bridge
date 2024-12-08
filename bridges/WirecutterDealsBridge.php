<?php

class WirecutterDealsBridge extends BridgeAbstract
{
    const NAME = 'Wirecutter Deals';
    const URI = 'https://www.nytimes.com/wirecutter/deals/';
    const DESCRIPTION = 'Deals from The Wirecutter';
    const MAINTAINER = 'Vynce';
    const CACHE_TIMEOUT = 900; // 15 minutes

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $json = $html->getElementById('__NEXT_DATA__');
        $data = json_decode($json->innertext());

        foreach ($data->props->pageProps->specialEvent->eventDeals as $deal) {
            $item = [];
            $item['uri'] = "https://www.nytimes.com/wirecutter/deals/#deal-{$deal->id}";
            $item['title'] = $deal->title;
            $item['timestamp'] = $deal->date;
            $item['content'] = $this->generateContent($deal);
            $item['categories'] = $deal->categories;
            $item['uid'] = strval($deal->id);

            $this->items[] = $item;
        }
    }

    public function getIcon()
    {
        return <<<'EOD'
        data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAD7klEQ
        VR4AWIgFoReXcVmdnKRuymg9WoAkiUJon2+8BnhiLP17bNtX+Bs27aN0bdtY8y1bZu9m1evIrZH
        VTW9HVsVuTNTndH5qirz5VuP7ddL3LZdk9y2AmZdk9z2TvaZP8lj3znFY/95ssdxxWPB4GHaRI1
        Lgq6T2ct/RyAWmExa+ySP7acZMecJlgPPK7cdOdlj/5i9qFcdTG44Ifb5Lk7Pwq5tXnUA8zbZbd
        s7O/zf8aaCT/U6zmc7r7ESaFZwAd1fvpmuzVkhel4xzeM8K+POZcHnhBbSQ5Vb6fmmffQAC3J5d
        IkQxDSfk5YNF9O2kQqazQClPC+fF1x4nPTOZcf+epuPemmA9JQZoXr6oCtMN+StSvL/tT+XP9+k
        lwnywr5HWCVIOFHwZxv3km5iVlE7rWMBd7CdD7OJtUEaoul+lyAn7G8Kkk6c7cXUQrrFCSAz0gH
        AOqb4HMcaAFDnwuz12I2XbdXL6f2uEP09kE+e0RoaYjMTgC16uaJE7d8ZDKcimTJqITcLmJrtj1
        Rto6fq99CSoSLqor604CGqkyYqjG26+U5aeogGelWV1oMVW2j1cAn/fnX2Mp7hP7EkY+XK12YG5
        vMkvK1oHT3dsIfWDpeiSnB6mfnBb5+jgdszOV4WWUwvt7ipm/pwFXztqqxl9HVfNl97s91n+L7a
        6jHPHZ7/vtHQWDI5ftITNbL62cZ99MdAXmIOWAbAEn+Dhq6mcsJdJ9b93aUbUkvTAIBjXzRUhBw
        x2ysiANAldBCU4UfdYazhrtMAICg4AL9zqdFIQLXZGzVVBdxetC4xEGhYCuDdzkDiGq7JzAk04A
        TyZQ5IvISX4n6lALAOWsbvPwfyTVQB7L+wBiUjcQDxCHYlBQAbuybDrsleriKj9RpklMzhxeYDS
        YFiVC8HIKiCK2NL6bPemIqMvtSg4WQOd5asT2U4gDIA9NMgjhucIAQAUsJvBYCZGlojmoMkSaiB
        OpMAgHa/68/G9UAjGL6JAKAJ7IMF3P/G/FXSCviAPjhYw4CAlKF8oWm/sNE4Bwvp3rJNdGlkkQH
        2utwVnKrrqIP7+Edr1cc/NqBeZXyAF2/Uy5RdD2IFrTe1Fd/HAEp23zZr34KjtcQB9cpMKrPQWv
        VxzHc6AgqRan9F+I8H1KuCNOi1Vi+1ULcycBN10ZP1u1Wlt423YdGAdIZ6haPqNB6r3cWFye6RS
        l6ae0eqyDFYCH1gSDCJFfGjVw1IZ6hXZjTBVnSx23WqZmZAOkO9TlRwHLt855IBfoB6NTjCktnb
        kHD8zq0OqFcISGi4cQRuRJ1Ldm1tYBfQcJBRUDIQEwiEloquhsaCoKBXznAmx/9MoY9pELa5wwA
        AAABJRU5ErkJggg==
        EOD;
    }

    private function jsonToHtml($node)
    {
        if ($node['type'] == 'text') {
            return htmlspecialchars($node['data'], ENT_QUOTES, 'UTF-8');
        }

        if ($node['type'] == 'tag') {
            $html = "<{$node['name']}";

            foreach ($node['attribs'] as $key => $value) {
                $html .= sprintf(
                    ' %s="%s"',
                    htmlspecialchars($key, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                );
            }

            $html .= '>';

            foreach ($node['children'] as $child) {
                $html .= $this->jsonToHtml($child);
            }

            $html .= "</{$node['name']}>";

            return $html;
        }

        return '';
    }

    private function generateContent($deal)
    {
        $img_link = $deal->image->source;
        $content = "<p><img src=\"https://cdn.thewirecutter.com/{$img_link}?width=314&amp;quality=75&amp;crop=3:2&amp;auto=webp\"></p>";

        $content .= "<p><strong>\${$deal->price}</strong> <del>\${$deal->streetPrice}</del></p>";

        foreach ($deal->buyButtons as $buy) {
            $content .= "<p>Buy from <a href=\"{$buy->url}\">$buy->merchant</a>";
            if ($buy->promo->effect) {
                $content .= " {$buy->promo->effect}";
            }
            if ($buy->promo->code) {
                $content .= " (Use promo code {$buy->promo->code})";
            }
            $content .= '</p>';
        }

        $content .= '<p>&nbsp;</p>';
        $structuredContent = json_decode($deal->structuredContent, true);
        foreach ($structuredContent as $node) {
            $content .= $this->jsonToHtml($node);
        }

        if ($deal->relatedArticle) {
            $review = $deal->relatedArticle;
            $content .= '<p>&nbsp;</p>';
            $content .= "<p>Read the review: <a href=\"https://www.nytimes.com/wirecutter{$review->link}\">{$review->title}</a></p>";
        }

        return $content;
    }
}
