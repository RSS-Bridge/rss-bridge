<?php

declare(strict_types=1);

class AlpineReleasesBridge extends BridgeAbstract {
    const NAME = 'Alpine Releases';
    const URI = 'https://alpinelinux.org/releases/';
    const DESCRIPTION = 'Alpine Linux releases with branch info';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 3600;

    private const CSS = [
        'table'      => 'border-collapse:collapse;margin-bottom:16px;font-size:0.9em;width:100%;max-width:600px',
        'thead'      => 'background-color:#f0f0f0;border-bottom:2px solid #ccc',
        'th'         => 'padding:8px 10px;border:1px solid #ddd;text-align:left;font-weight:bold',
        'tr_even'    => 'background-color:#f9f9f9',
        'tr_odd'     => 'background-color:#ffffff',
        'td'         => 'padding:6px 10px;border:1px solid #ddd',
        'td_version' => 'padding:6px 10px;border:1px solid #ddd;font-weight:bold',
    ];

    public function collectData(): void {
        $branches = $this->getBranchesInfo();
        $feed = simplexml_load_string(getContents('https://alpinelinux.org/atom.xml'));
        
        if (!$feed) return;

        foreach ($feed->entry as $entry) {
            $title = (string)$entry->title;
            
            if (!preg_match('/alpine.*\d+\.\d+(\.\d+)?.*(released|releases)/i', $title)) continue;
            
            preg_match_all('/(\d+\.\d+(?:\.\d+)?)/', $title, $matches);
            $versions = $matches[1] ?? [];
            
            $timestamp = isset($entry->updated) ? strtotime((string)$entry->updated) ?: 0 : 0;
            
            $content = (string)($entry->content ?? $entry->summary ?? '');
            $plainContent = trim(strip_tags($content));
            if ($plainContent === $title || $plainContent === '') $content = '';
            
            $branchesMeta = $this->buildBranchesMeta($versions, $branches);
            if ($branchesMeta) $content = $branchesMeta . ($content ? "<br>$content" : '');
            
            $this->items[] = [
                'title'     => $title,
                'uri'       => (string)($entry->link['href'] ?? ''),
                'author'    => (string)($entry->author->name ?? ''),
                'timestamp' => $timestamp,
                'uid'       => (string)($entry->id ?? ''),
                'content'   => $content ?: $title,
            ];
        }
        
        usort($this->items, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
    }
    
    private function cleanText(string $text): string {
        if (!$text) return '';
        
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\xE2\x80\xA2", "\xC2\xB7", "\xE2\x97\x8F", "\xE2\x97\x8B", "\xE2\x97\xA6", "\xE2\xA6\xBF"], ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        
        return is_string($text) ? trim($text) : '';
    }
    
    private function getBranchesInfo(): array {
        $html = getSimpleHTMLDOMCached(self::URI, 86400);
        if (!$html) return [];
        
        $branches = [];
        
        foreach ($html->find('table tr') as $row) {
            $cells = $row->find('td');
            if (count($cells) < 5) continue;
            
            $branchName = $this->cleanText((string)($cells[0]->plaintext ?? ''));
            if (strpos($branchName, 'v') === 0) $branchName = substr($branchName, 1);
            
            $branchDate = $this->cleanText((string)($cells[1]->plaintext ?? ''));
            $endOfSupport = $this->cleanText((string)($cells[4]->plaintext ?? ''));
            
            preg_match_all('/(\d+\.\d+(?:\.\d+)?)/', (string)$cells[3]->plaintext, $matches);
            
            foreach ($matches[1] ?? [] as $version) {
                $branches[$version] = compact('branchName', 'branchDate', 'endOfSupport');
            }
        }
        
        return $branches;
    }
    
    private function buildBranchesMeta(array $versions, array $branches): string {
        usort($versions, fn($a, $b) => version_compare($b, $a));
        
        $rows = '';
        foreach ($versions as $i => $version) {
            if (!isset($branches[$version])) continue;
            
            $info = $branches[$version];
            $rowStyle = $i % 2 ? self::CSS['tr_even'] : self::CSS['tr_odd'];
            $esc = fn($s) => htmlspecialchars($s ?: '', ENT_QUOTES, 'UTF-8');
            
            $rows .= sprintf(
                '<tr style="%s"><td style="%s">%s</td><td style="%s">%s</td><td style="%s">%s</td><td style="%s">%s</td></tr>',
                $rowStyle, self::CSS['td_version'], $esc($version),
                self::CSS['td'], $esc($info['branchName']),
                self::CSS['td'], $esc($info['branchDate']),
                self::CSS['td'], $esc($info['endOfSupport'])
            );
        }
        
        if (!$rows) return '';
        
        $th = self::CSS['th'];
        return sprintf(
            '<table style="%s"><thead style="%s"><tr><th style="%s">Version</th><th style="%s">Branch</th><th style="%s">Branch date</th><th style="%s">End of support</th></tr></thead><tbody>%s</tbody></table>',
            self::CSS['table'], self::CSS['thead'], $th, $th, $th, $th, $rows
        );
    }
}