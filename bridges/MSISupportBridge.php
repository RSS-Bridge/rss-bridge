<?php

declare(strict_types=1);

class MSISupportBridge extends BridgeAbstract
{
    const NAME = 'MSI Support';
    const URI = 'https://www.msi.com/';
    const DESCRIPTION = 'Returns BIOS, drivers, manuals, and utilities updates for MSI products via internal API';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 14400;
    const VALID_TYPES = ['bios', 'driver', 'manual', 'utility'];
    const API_BASE_URL = 'https://www.msi.com/api/v1/product/support/panel';
    const PARAMETERS = [
        [
            'url' => [
                'name' => 'Support page URL',
                'type' => 'text',
                'required' => true,
                'title' => 'Full URL of the product support page on msi.com (hash fragments like #bios or #utility are supported)'
            ],
            'hide_download_button' => [
                'name' => 'Hide download button',
                'type' => 'checkbox',
                'title' => 'Check this box to hide the download button from feed items'
            ]
        ]
    ];

    private const CSS = [
        'item'     => 'font-family:sans-serif;line-height:1.6;color:#333',
        'p'        => 'margin:8px 0',
        'link'     => 'color:#cc0000;text-decoration:none;font-weight:500',
        'label'    => 'font-weight:bold;color:#111',
        'download' => 'display:inline-block;margin-top:10px;padding:8px 16px;background:#cc0000;color:#fff;text-decoration:none;border-radius:4px;font-weight:500',
    ];

    private $productInfo = null;

    public function getIcon()
    {
        return 'https://www.msi.com/favicon.ico';
    }

    public function getName()
    {
        $info = $this->getProductInfo();
        if (!$info) {
            return parent::getName();
        }

        $displayName = !empty($info['sub_product']) ? $info['sub_product'] : $info['product'];
        $name = str_replace('-', ' ', $displayName);

        if (!empty($info['fragment'])) {
            $fragmentName = strtolower($info['fragment']) === 'bios' ? 'BIOS' : ucfirst($info['fragment']);
            $name .= ' (' . $fragmentName . ')';
        }

        return $name;
    }

    public function getURI()
    {
        return $this->getInput('url') ?: parent::getURI();
    }

    private function getProductInfo()
    {
        if ($this->productInfo !== null) {
            return $this->productInfo;
        }

        $url = $this->getInput('url');
        if (!$url) {
            return null;
        }

        $parsedUrl = parse_url($url);
        $segments = array_values(array_filter(
            explode('/', trim($parsedUrl['path'] ?? '', '/')),
            function ($s) {
                return $s !== '';
            }
        ));

        if (count($segments) < 2) {
            return null;
        }

        if (end($segments) === 'support') {
            array_pop($segments);
        }

        $product = array_pop($segments);
        $category = array_pop($segments);

        $subProduct = '';
        if (!empty($parsedUrl['query'])) {
            $queryParams = [];
            parse_str($parsedUrl['query'], $queryParams);
            if (!empty($queryParams['sub_product'])) {
                $subProduct = $queryParams['sub_product'];
            }
        }

        $this->productInfo = [
            'product'     => $product,
            'category'    => $category,
            'fragment'    => $parsedUrl['fragment'] ?? '',
            'sub_product' => $subProduct
        ];

        return $this->productInfo;
    }

    private function getDownloadTypes()
    {
        $fragment = $this->getProductInfo()['fragment'] ?? '';
        return in_array($fragment, self::VALID_TYPES, true) ? [$fragment] : self::VALID_TYPES;
    }

    private function fetchApiData($type)
    {
        $info = $this->getProductInfo();
        if (!$info) {
            return null;
        }

        $apiProduct = !empty($info['sub_product']) ? $info['sub_product'] : $info['product'];

        try {
            $url = self::API_BASE_URL . '?product=' . urlencode($apiProduct) . '&type=' . urlencode($type);
            $json = getContents($url, ['Accept: application/json']);
            $data = json_decode($json, true);

            return ($data && isset($data['result']['downloads'])) ? $data['result']['downloads'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function isSequentialArray($array)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        $i = 0;
        foreach ($array as $key => $val) {
            if ($key !== $i++) {
                return false;
            }
        }
        return true;
    }

    private function decodeHtml($str)
    {
        return empty($str) ? '' : html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function cleanDescription($html)
    {
        if (empty($html)) {
            return '';
        }

        $text = $this->decodeHtml($html);
        $text = preg_replace('/\s*(?:style|on\w+)\s*=\s*(["\']).*?\1/i', '', $text);
        $text = strip_tags($text, '<a><br><p>');

        $linkStyle = self::CSS['link'];
        $text = preg_replace_callback('/<a\s+([^>]*?)>/is', function ($m) use ($linkStyle) {
            $attrs = $m[1];
            if (stripos($attrs, 'target=') === false) {
                $attrs .= ' target="_blank"';
            }
            if (stripos($attrs, 'rel=') === false) {
                $attrs .= ' rel="noopener noreferrer"';
            }
            if (stripos($attrs, 'style=') === false) {
                $attrs .= ' style="' . $linkStyle . '"';
            }
            return '<a ' . trim($attrs) . '>';
        }, $text);

        return nl2br($text);
    }

    private function buildFeedItem($file, $subCategory, $info, $hideAttachments)
    {
        $title = $this->decodeHtml($file['download_title'] ?? $subCategory);
        $version = $this->decodeHtml($file['download_version'] ?? '');
        $itemTitle = "[{$subCategory}] {$title}" . (!empty($version) ? " - {$version}" : '');

        $uri = "https://www.msi.com/{$info['category']}/{$info['product']}/support";
        if (!empty($info['sub_product'])) {
            $uri .= '?sub_product=' . urlencode($info['sub_product']);
        }
        if (!empty($info['fragment'])) {
            $uri .= '#' . $info['fragment'];
        }

        $content = '<div style="' . self::CSS['item'] . '">';

        if (!empty($file['download_description'])) {
            $desc = $this->cleanDescription($file['download_description']);
            $content .= '<p style="' . self::CSS['p'] . '"><span style="' . self::CSS['label'] . '">Description:</span><br>' . $desc . '</p>';
        }
        if (!empty($file['os'])) {
            $os = htmlspecialchars($this->decodeHtml($file['os']), ENT_QUOTES, 'UTF-8');
            $content .= '<p style="' . self::CSS['p'] . '"><span style="' . self::CSS['label'] . '">OS:</span> ' . $os . '</p>';
        }
        if (!empty($file['download_size'])) {
            $sizeMb = round($file['download_size'] / (1024 * 1024), 2);
            $content .= '<p style="' . self::CSS['p'] . '"><span style="' . self::CSS['label'] . '">Size:</span> ' . $sizeMb . ' MB</p>';
        }
        if (!$hideAttachments && !empty($file['download_url'])) {
            $downloadUrl = htmlspecialchars($file['download_url']);
            $downloadStyle = self::CSS['download'];
            $content .= sprintf(
                '<p style="%s"><a href="%s" style="%s" target="_blank" rel="noopener noreferrer">Download</a></p>',
                self::CSS['p'],
                $downloadUrl,
                $downloadStyle
            );
        }

        $content .= '</div>';

        $item = [
            'title'   => $itemTitle,
            'uri'     => $uri,
            'content' => $content,
            'uid'     => $file['download_id'] ?? md5($itemTitle)
        ];

        if (!empty($file['download_release'])) {
            $item['timestamp'] = strtotime($file['download_release']);
        }

        return $item;
    }

    public function collectData()
    {
        $info = $this->getProductInfo();
        if (!$info || !$info['product'] || !$info['category']) {
            throwClientException('Invalid URL format or could not extract product info. Expected format: https://www.msi.com/Category/Product-ID/support');
        }

        $hideAttachments = (bool) $this->getInput('hide_download_button');
        $allItems = [];

        foreach ($this->getDownloadTypes() as $type) {
            $downloads = $this->fetchApiData($type);
            if (!$downloads) {
                continue;
            }

            foreach ($downloads as $subCategory => $files) {
                if (!$this->isSequentialArray($files)) {
                    continue;
                }

                foreach ($files as $file) {
                    if (!is_array($file)) {
                        continue;
                    }
                    if (empty($file['download_url']) && empty($file['download_title'])) {
                        continue;
                    }
                    $allItems[] = $this->buildFeedItem($file, $subCategory, $info, $hideAttachments);
                }
            }
        }

        if (empty($allItems)) {
            return;
        }

        usort($allItems, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        foreach ($allItems as $item) {
            $this->items[] = $item;
        }
    }
}