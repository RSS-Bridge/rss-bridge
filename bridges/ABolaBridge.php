<?php

class ABolaBridge extends BridgeAbstract
{
    const NAME = 'A Bola';
    const URI = 'https://abola.pt/';
    const DESCRIPTION = 'Returns news from the Portuguese sports newspaper A BOLA.PT';
    const MAINTAINER = 'rmscoelho';
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'News Feed',
                'type' => 'list',
                'title' => 'Feeds from the Portuguese sports newspaper A BOLA.PT',
                'values' => [
                    'Últimas' => 'Nnh/Noticias',
                    'Seleção Nacional' => 'Selecao/Noticias',
                    'Futebol Nacional' => [
                        'Notícias' => 'Nacional/Noticias',
                        'Primeira Liga' => 'Nacional/Liga/Noticias',
                        'Liga 2' => 'Nacional/Liga2/Noticias',
                        'Liga 3' => 'Nacional/Liga3/Noticias',
                        'Liga Revelação' => 'Nacional/Liga-Revelacao/Noticias',
                        'Campeonato de Portugal' => 'Nacional/Campeonato-Portugal/Noticias',
                        'Distritais' => 'Nacional/Distritais/Noticias',
                        'Taça de Portugal' => 'Nacional/TPortugal/Noticias',
                        'Futebol Feminino' => 'Nacional/FFeminino/Noticias',
                        'Futsal' => 'Nacional/Futsal/Noticias',
                    ],
                    'Futebol Internacional' => [
                        'Notícias' => 'Internacional/Noticias/Noticias',
                        'Liga dos Campeões' => 'Internacional/Liga-dos-campeoes/Noticias',
                        'Liga Europa' => 'Internacional/Liga-europa/Noticias',
                        'Liga Conferência' => 'Internacional/Liga-conferencia/Noticias',
                        'Liga das Nações' => 'Internacional/Liga-das-nacoes/Noticias',
                        'UEFA Youth League' => 'Internacional/Uefa-Youth-League/Noticias',
                    ],
                    'Mercado' => 'Mercado',
                    'Modalidades' => 'Modalidades/Noticias',
                    'Motores' => 'Motores/Noticias',
                ]
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://abola.pt/img/icons/favicon-96x96.png';
    }

    public function getName()
    {
        $feed = $this->getInput('feed');
        if ($this->getInput('feed') !== null && $this->getInput('feed') !== '') {
            $name = explode('/', $feed);
            if ($name[0] === 'Selecao') {
                $name = 'Seleção';
            } elseif ($name[0] === 'Nnh') {
                $name = 'Últimas';
            } elseif ($name[0] === 'Mercado') {
                $name = $name[0];
            } elseif ($name[0] === 'Modalidades') {
                $name = $name[0];
            } elseif ($name[0] === 'Motores') {
                $name = $name[0];
            } else {
                $name = $name[1];
                $concatName = '';

                if (str_contains($name, '-')) {
                    $name = explode('-', $name);
                    foreach ($name as $item) {
                        $concatName .= ucfirst($item) . ' ';
                    }
                    $name = $concatName;
                }
            }
            return self::NAME . ' | ' . ucfirst($name);
        }
        return self::NAME;
    }

    public function getURI()
    {
        return self::URI . $this->getInput('feed');
    }

    public function collectData()
    {
        $url = sprintf('https://abola.pt/%s', $this->getInput('feed'));
        $dom = getSimpleHTMLDOM($url);
        if ($this->getInput('feed') !== 'Mercado') {
            $dom = $dom->find('div#body_Todas1_upNoticiasTodas', 0);
        } else {
            $dom = $dom->find('div#body_NoticiasMercado_upNoticiasTodas', 0);
        }
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('div.media') as $key => $article) {
            //Get thumbnail
            $image = $article->find('.media-img', 0)->style;
            $image = preg_replace('/background-image: url\(/i', '', $image);
            $image = substr_replace($image, '', -4);
            $image = preg_replace('/https:\/\//i', '', $image);
            $image = preg_replace('/www\./i', '', $image);
            $image = preg_replace('/\/\//', '/', $image);
            $image = preg_replace('/\/\/\//', '//', $image);
            $image = substr($image, 7);
            $image = 'https://' . $image;
            $image = preg_replace('/ptimg/', 'pt/img', $image);
            $image = preg_replace('/\/\/bola/', 'www.abola', $image);
            //Timestamp
            $date = $article->find("span#body_Todas1_rptNoticiasTodas_lblData_$key", 0)->plaintext;
            $time = $article->find("span#body_Todas1_rptNoticiasTodas_lblHora_$key", 0)->plaintext;
            if ($date === null) {
                $date = date('Y/m/d');
            } else {
                $date = preg_replace('/\./', '/', $date);
            }
            $date = explode('/', $date);
            $time = explode(':', $time);
            $year = $date[0];
            $month = $date[1];
            $day = $date[2];
            $hour = $time[0];
            $minute = $time[1];
            $timestamp = mktime($hour, $minute, 0, $month, $day, $year);
            //Content
            $content = '<p>' . $article->find('.media-texto > span', 0)->plaintext . '</p>';
            $content = $content . '<br><img src="' . $image . '" alt="' . $article->find('h2', 0)->plaintext . '" />';
            $a = $article->find('.media-body > a', 0);
            $this->items[] = [
                'title' => $a->find('h4 span', 0)->plaintext,
                'uri' => $a->href,
                'content' => $content,
                'timestamp' => $timestamp,
            ];
        }
    }
}
