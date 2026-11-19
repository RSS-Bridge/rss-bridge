<?php

declare(strict_types=1);

class WawacityBridge extends BridgeAbstract
{
    const NAME = 'Wawacity';
    // Last known-good mirror, used only as a fallback if the domain can't be
    // resolved dynamically (see resolveBaseUri())
    const URI = 'https://www.wawacity.poker';
    const DESCRIPTION = 'Fetches the latest on wawacity';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [
        'categorie' => [
            'categorie' => [
                'name' => 'Catégorie',
                'type' => 'list',
                'title' => 'Catégorie',
                'values' => [
                    'Derniers ajouts (accueil)' => 'accueil',
                    'Films' => 'films',
                    'Séries' => 'series',
                    'Jeux' => 'jeux',
                    'Musiques' => 'musiques',
                    'Ebooks' => 'ebooks',
                    'Animés' => 'mangas',
                    'Logiciels' => 'logiciels',
                    'Mobiles' => 'mobiles',
                    'Autres vidéos' => 'autres-videos',
                    'Divers' => 'divers',
                ]
            ],
            'sous_categorie' => [
                'name' => 'Sous-catégorie',
                'type' => 'text',
                'title' => <<<EOT
                    Optionnel. Pour une catégorie du catalogue, reprend le paramètre "s" du site (ex: "bd" ou "mangas" pour Ebooks,
                    "vf" ou "vostfr" pour Séries, "ps4" ou "pc" pour Jeux ; voir le menu de navigation du site pour la liste complète)
                    Pour "Derniers ajouts (accueil)", choisit le widget de la page d\'accueil : exclusivites, films, films-bluray, '
                    films-4k, series-vostfr, series-vf, series-4k, jeux, musiques, ebooks, animes, logiciels, mobiles, '
                    autres-videos, divers (laisser vide = tous les widgets, avec la mention "Ajout de l\'épisode X" pour les séries/animés).
                    EOT,
                'exampleValue' => 'series-vostfr',
                'required' => false,
            ]
        ]
    ];
    const CACHE_TIMEOUT = 18000; // every 5h

    // wawacity is behind Cloudflare, which returns a 520 error to
    // requests that don't look like they come from a real browser.
    const REQUEST_HEADERS = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Upgrade-Insecure-Requests: 1',
    ];

    // Slug (used as sous_categorie) => exact "wa-block-title" text of the matching homepage widget
    const HOMEPAGE_WIDGETS = [
        'exclusivites' => 'Exclusivités (populaires)',
        'films' => 'Télécharger Films',
        'films-bluray' => 'Télécharger Films Blu-Ray',
        'films-4k' => 'Télécharger Films ULTRA HD 4K',
        'series-vostfr' => 'Télécharger Séries VOSTFR',
        'series-vf' => 'Télécharger Séries VF',
        'series-4k' => 'Télécharger Séries MULTI 4K',
        'jeux' => 'Télécharger Jeux',
        'musiques' => 'Télécharger Musiques',
        'ebooks' => 'Télécharger Ebooks',
        'animes' => 'Télécharger Animés',
        'logiciels' => 'Télécharger Logiciels',
        'mobiles' => 'Télécharger Mobiles',
        'autres-videos' => 'Télécharger Autres vidéo',
        'divers' => 'Télécharger divers',
    ];

    // wawacity's domain changes often (blocking, DNS seizures...). This
    // "we moved" landing page stays stable and always links to the current
    // mirror through its #manual-redirect button, so we resolve the real
    // domain from there instead of hard-coding it.
    const INFO_URI = 'https://wawacity-info.com/';
    const BASE_URI_CACHE_KEY = 'base_uri';
    const BASE_URI_CACHE_TTL = 18000; // re-check every 5h

    private string $baseUri;

    public function collectData()
    {
        $this->baseUri = $this->resolveBaseUri();

        $categorie = $this->getInput('categorie');
        $sousCategorie = trim((string) $this->getInput('sous_categorie'));

        if ($categorie === 'accueil') {
            $this->collectHomepageData($sousCategorie);
        } else {
            $this->collectCatalogueData($categorie, $sousCategorie);
        }
    }

    public function getURI(): string
    {
        return $this->baseUri ?? parent::getURI();
    }

    /**
     * Resolves the current wawacity mirror domain from wawacity-info.com's
     * #manual-redirect link, caching the result so we don't hit that page on
     * every single request. Falls back to the last known-good domain (the
     * cached value if we have one, otherwise the hard-coded const URI) if the
     * landing page itself is unreachable or has changed shape.
     */
    private function resolveBaseUri(): string
    {
        $cached = $this->loadCacheValue(self::BASE_URI_CACHE_KEY);

        try {
            $html = getSimpleHTMLDOM(self::INFO_URI, self::REQUEST_HEADERS);
            $redirectDom = $html->find('#manual-redirect', 0);
            $href = $redirectDom ? trim($redirectDom->href) : '';

            if ($href !== '' && preg_match('#^https://[^/]+#', $href, $matches)) {
                $resolved = $matches[0];
                $this->saveCacheValue(self::BASE_URI_CACHE_KEY, $resolved, self::BASE_URI_CACHE_TTL);
                return $resolved;
            }
        } catch (\Throwable $e) {
            // Fall through to the cached/hard-coded fallback below.
        }

        return $cached ?? self::URI;
    }

    private function collectCatalogueData($categorie, $sousCategorie)
    {
        $query = 'p=' . rawurlencode($categorie);
        if ($sousCategorie !== '') {
            $query .= '&s=' . rawurlencode($sousCategorie);
        }
        $url = $this->baseUri . '/?' . $query;

        $html = getSimpleHTMLDOM($url, self::REQUEST_HEADERS) or throwServerException('Could not request ' . $url);

        $elementsDom = $html->find('#wa-mid-blocks .wa-post-detail-item');
        foreach ($elementsDom as $elementDom) {
            $titleDom = $elementDom->find('.wa-sub-block-title a', 0);
            $uri = $this->toAbsoluteUri($titleDom->href);
            $title = html_entity_decode(trim($titleDom->plaintext), ENT_QUOTES);

            $imgDom = $elementDom->find('.cover img', 0);
            $imgSrc = $imgDom ? $this->baseUri . $imgDom->src : null;

            $descriptionDom = $elementDom->find('.col-md-10 p', 0);
            $description = $descriptionDom ? html_entity_decode(trim($descriptionDom->plaintext), ENT_QUOTES) : '';

            $content = '<a href="' . $uri . '">';
            if ($imgSrc) {
                $content .= '<img src="' . $imgSrc . '" /><br>';
            }
            $content .= $title . '</a>';
            if ($description !== '') {
                $content .= '<br>' . $description;
            }

            $item = [];
            $item['uri'] = $uri;
            $item['title'] = 'Wawacity : ' . $title;
            $item['author'] = 'floviolleau';
            $item['content'] = $content;
            $item['uid'] = hash('sha256', $uri);

            $this->items[] = $item;
        }
    }

    private function collectHomepageData($widget)
    {
        if ($widget !== '' && !array_key_exists($widget, self::HOMEPAGE_WIDGETS)) {
            throwServerException(
                'Sous-catégorie inconnue pour "Derniers ajouts (accueil)": ' . $widget
                . '. Valeurs possibles: ' . implode(', ', array_keys(self::HOMEPAGE_WIDGETS)) . '.'
            );
        }

        $url = $this->baseUri;
        $html = getSimpleHTMLDOM($url, self::REQUEST_HEADERS) or throwServerException('Could not request ' . $url);

        $blocksDom = $html->find('#wa-mid-blocks .wa-block');
        foreach ($blocksDom as $blockDom) {
            $blockTitleDom = $blockDom->find('.wa-block-title', 0);
            if (!$blockTitleDom) {
                continue;
            }
            $blockTitle = html_entity_decode(trim($blockTitleDom->plaintext), ENT_QUOTES);

            if ($widget !== '' && $blockTitle !== self::HOMEPAGE_WIDGETS[$widget]) {
                continue;
            }

            foreach ($blockDom->find('a.thumbnail') as $thumbnailDom) {
                $uri = $this->toAbsoluteUri($thumbnailDom->href);
                $title = html_entity_decode(trim($thumbnailDom->title ?: ''), ENT_QUOTES);

                $imgDom = $thumbnailDom->find('img', 0);
                $imgSrc = $imgDom ? $this->baseUri . $imgDom->src : null;
                if ($title === '' && $imgDom) {
                    $title = html_entity_decode(trim($imgDom->attr['alt'] ?? ''), ENT_QUOTES);
                }

                $episode = null;
                foreach ($thumbnailDom->find('div') as $divDom) {
                    $text = html_entity_decode(trim($divDom->plaintext), ENT_QUOTES);
                    if (stripos($text, 'Ajout de') !== false) {
                        $episode = $text;
                        break;
                    }
                }

                $displayTitle = $episode ? $title . ' - ' . $episode : $title;

                $content = '<a href="' . $uri . '">';
                if ($imgSrc) {
                    $content .= '<img src="' . $imgSrc . '" /><br>';
                }
                $content .= $displayTitle . '</a>';

                $item = [];
                $item['uri'] = $uri;
                $item['title'] = 'Wawacity : ' . $blockTitle . ' : ' . $displayTitle;
                $item['author'] = 'floviolleau';
                $item['content'] = $content;
                $item['uid'] = hash('sha256', $blockTitle . '|' . $uri . '|' . $episode);

                $this->items[] = $item;
            }
        }
    }

    private function toAbsoluteUri($link)
    {
        return $this->baseUri . (substr($link, 0, 1) === '/' ? $link : '/' . $link);
    }
}
