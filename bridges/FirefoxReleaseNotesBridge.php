<?php

declare(strict_types=1);

class FirefoxReleaseNotesBridge extends BridgeAbstract
{
    const NAME = 'Firefox Release Notes';
    const URI = 'https://www.firefox.com/en-US/releases/';
    const DESCRIPTION = 'Returns recent Firefox releases with changelogs for each version';
    const MAINTAINER = 'LordArrin';
    const PARAMETERS = [];

    private const CSS = [
        '.ff-release-content' => 'line-height:1.6;color:#20123a;max-width:720px',
        '.ff-release-content h3' => 'font-size:1.2em;font-weight:600;margin:1.5em 0 .5em;padding:.3em .5em;border-left:4px solid;background:linear-gradient(90deg,#f9f9fa,#fff)',
        '.ff-release-content ul' => 'margin:0.5em 0;padding-left:1.5em;list-style-type:disc;list-style-position:outside',
        '.ff-release-content li' => 'margin:0.6em 0;padding:0.2em 0;list-style-type:disc',
        '.ff-release-content p' => 'margin:0.5em 0',
        '.ff-release-content code' => 'background:#f0f0f4;padding:0.1em 0.4em;border-radius:3px;font-size:0.92em;color:#b5007f',
        '.ff-release-content a' => 'color:#0060df;text-decoration:none;border-bottom:1px dotted #0060df',
        '.ff-release-content a:hover' => 'color:#b5007f',
        '.ff-release-content img' => 'max-width:100%;height:auto;border-radius:4px;margin:0.5em 0;box-shadow:0 1px 3px rgba(0,0,0,0.1)',
        '.ff-release-intro' => 'background:#fff4e6;border-left:4px solid #ff9500;padding:0.8em 1em;margin-bottom:1em;border-radius:0 4px 4px 0;font-style:italic',
    ];

    private const SECTION_COLORS = [
        'new'        => '#058b00',
        'fixed'      => '#0060df',
        'changed'    => '#b5007f',
        'developer'  => '#20123a',
        'html5'      => '#008ea4',
        'community'  => '#7542e5',
        'labs'       => '#ff9400',
        'known'      => '#c45a00',
        'security'   => '#d70022',
        'enterprise' => '#3f3f3f',
    ];

    private const RELEASE_LIMIT = 10;

    public function collectData(): void
    {
        $releases = $this->prepareReleases();

        foreach ($releases as $release) {
            $this->items[] = $this->buildReleaseItem($release);
        }

        $this->sortItemsByDate();
    }

    private function fetchReleaseLinks(): array
    {
        $html = getSimpleHTMLDOM(self::URI);
        if (!$html) {
            throwClientException('Failed to load Firefox releases page.');
        }

        $html = defaultLinkTo($html, self::URI);
        $links = $html->find('a[href*="/releasenotes/"]') ?: $html->find('a[href*="/firefox/"]');

        $releases = [];
        foreach ($links as $link) {
            $text = trim($link->plaintext);
            if (preg_match('/(\d+(\.\d+)+)/', $text, $matches)) {
                $releases[] = ['version' => $matches[1], 'url' => $link->href];
            }
        }

        return $releases;
    }

    private function prepareReleases(): array
    {
        $releases = $this->fetchReleaseLinks();

        $unique = [];
        $seen = [];
        foreach ($releases as $release) {
            if (!in_array($release['url'], $seen, true)) {
                $unique[] = $release;
                $seen[] = $release['url'];
            }
        }

        return array_slice($unique, 0, self::RELEASE_LIMIT);
    }

    private function buildReleaseItem(array $release): array
    {
        $item = [
            'title' => 'Firefox ' . $release['version'],
            'uri' => $release['url'],
            'uid' => $release['url'],
        ];

        $notesHtml = getSimpleHTMLDOM($release['url']);
        if (!$notesHtml) {
            $item['content'] = '<p>Failed to load release notes page.</p>';
            return $item;
        }

        $notesHtml = defaultLinkTo($notesHtml, $release['url']);

        $timestamp = $this->extractReleaseDate($notesHtml);
        if ($timestamp) {
            $item['timestamp'] = $timestamp;
        }

        $item['content'] = $this->buildReleaseContent($notesHtml);

        return $item;
    }

    private function extractReleaseDate(object $html): ?int
    {
        $dateElement = $html->find('.c-release-date', 0);
        if ($dateElement) {
            $dateText = trim($dateElement->plaintext);
            $timestamp = strtotime($dateText);
            return $timestamp ?: null;
        }

        $timeTag = $html->find('time', 0);
        if ($timeTag) {
            $dateText = $timeTag->datetime ?: $timeTag->plaintext;
            $timestamp = strtotime($dateText);
            return $timestamp ?: null;
        }

        $pageText = $html->plaintext;
        if (preg_match('/([A-Z][a-z]+ \d{1,2}, \d{4}|\d{1,2} [A-Z][a-z]+ \d{4}|\d{4}-\d{2}-\d{2})/', $pageText, $matches)) {
            $timestamp = strtotime($matches[1]);
            return $timestamp ?: null;
        }

        return null;
    }

    private function buildReleaseContent(object $html): string
    {
        $css = "<style>\n";
        foreach (self::CSS as $selector => $rules) {
            $css .= $selector . ' { ' . $rules . " }\n";
        }
        $css .= '</style>';

        $parts = [$css, '<div class="ff-release-content">'];

        $firstText = $html->find('.c-release-first-text', 0);
        if ($firstText && trim($firstText->plaintext)) {
            $parts[] = '<div class="ff-release-intro">' . trim($firstText->innertext) . '</div>';
        }

        $sections = $this->extractSections($html);
        foreach ($sections as $section) {
            $parts[] = $section;
        }

        $parts[] = '</div>';

        return implode("\n", $parts);
    }

    private function extractSections(object $html): array
    {
        $notesBlock = $html->find('section.c-release-notes', 0);
        if (!$notesBlock) {
            return [];
        }

        $sections = [];
        foreach ($notesBlock->find('div[id]') as $div) {
            $section = $this->buildSection($div);
            if ($section) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    private function buildSection(object $div): ?string
    {
        $heading = $div->find('.fl-c-release-notes-heading', 0);
        if (!$heading) {
            return null;
        }

        $title = trim($heading->plaintext);
        if (empty($title)) {
            return null;
        }

        $mainContent = $div->find('.mzp-l-main', 0);
        if (!$mainContent) {
            return null;
        }

        $lists = $mainContent->find('ul');
        if (empty($lists)) {
            return null;
        }

        $items = '';
        foreach ($lists as $list) {
            foreach ($list->find('li.release-note') as $note) {
                $noteContent = $note->find('.release-note-content', 0);
                if (!$noteContent) {
                    continue;
                }

                $content = trim($noteContent->innertext);
                if (empty($content)) {
                    continue;
                }

                $hasNestedList = $noteContent->find('ul', 0) || $noteContent->find('ol', 0);
                $items .= $hasNestedList ? $content : '<li>' . $content . '</li>';
            }
        }

        if (empty($items)) {
            return null;
        }

        $sectionId = strtolower(trim($div->id));
        $color = self::SECTION_COLORS[$sectionId] ?? '#20123a';

        $html = '<h3 style="border-left-color: ' . $color . '; color: ' . $color . ';">';
        $html .= htmlspecialchars($title);
        $html .= '</h3>';
        $html .= '<ul>' . $items . '</ul>';

        return $html;
    }

    private function sortItemsByDate(): void
    {
        usort($this->items, function ($a, $b): int {
            $timeA = $a['timestamp'] ?? 0;
            $timeB = $b['timestamp'] ?? 0;
            return $timeB <=> $timeA;
        });
    }
}