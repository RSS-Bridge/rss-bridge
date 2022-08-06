<?php

/**
 * JsonFormat - JSON Feed Version 1
 * https://jsonfeed.org/version/1
 *
 * Validators:
 * https://validator.jsonfeed.org
 * https://github.com/vigetlabs/json-feed-validator
 */
class JsonFormat extends FormatAbstract
{
    const MIME_TYPE = 'application/json';

    const VENDOR_EXCLUDES = [
        'author',
        'title',
        'uri',
        'timestamp',
        'content',
        'enclosures',
        'categories',
        'uid',
    ];

    public function stringify()
    {
        $https = $_SERVER['HTTPS'] ?? null;
        $urlPrefix = $https === 'on' ? 'https://' : 'http://';
        $urlHost = $_SERVER['HTTP_HOST'] ?? '';
        $urlRequest = $_SERVER['REQUEST_URI'] ?? '';

        $extraInfos = $this->getExtraInfos();

        $data = [
            'version' => 'https://jsonfeed.org/version/1',
            'title' => (!empty($extraInfos['name'])) ? $extraInfos['name'] : $urlHost,
            'home_page_url' => (!empty($extraInfos['uri'])) ? $extraInfos['uri'] : REPOSITORY,
            'feed_url' => $urlPrefix . $urlHost . $urlRequest
        ];

        if (!empty($extraInfos['icon'])) {
            $data['icon'] = $extraInfos['icon'];
            $data['favicon'] = $extraInfos['icon'];
        }

        $items = [];
        foreach ($this->getItems() as $item) {
            $entry = [];

            $entryAuthor = $item->getAuthor();
            $entryTitle = $item->getTitle();
            $entryUri = $item->getURI();
            $entryTimestamp = $item->getTimestamp();
            $entryContent = $item->getContent() ? sanitize_html($item->getContent()) : '';
            $entryEnclosures = $item->getEnclosures();
            $entryCategories = $item->getCategories();

            $vendorFields = $item->toArray();
            foreach (self::VENDOR_EXCLUDES as $key) {
                unset($vendorFields[$key]);
            }

            $entry['id'] = $item->getUid();

            if (empty($entry['id'])) {
                $entry['id'] = $entryUri;
            }

            if (!empty($entryTitle)) {
                $entry['title'] = $entryTitle;
            }
            if (!empty($entryAuthor)) {
                $entry['author'] = [
                    'name' => $entryAuthor
                ];
            }
            if (!empty($entryTimestamp)) {
                $entry['date_modified'] = gmdate(\DATE_ATOM, $entryTimestamp);
            }
            if (!empty($entryUri)) {
                $entry['url'] = $entryUri;
            }
            if (!empty($entryContent)) {
                if (is_html($entryContent)) {
                    $entry['content_html'] = $entryContent;
                } else {
                    $entry['content_text'] = $entryContent;
                }
            }
            if (!empty($entryEnclosures)) {
                $entry['attachments'] = [];
                foreach ($entryEnclosures as $enclosure) {
                    $entry['attachments'][] = [
                        'url' => $enclosure,
                        'mime_type' => parse_mime_type($enclosure)
                    ];
                }
            }
            if (!empty($entryCategories)) {
                $entry['tags'] = [];
                foreach ($entryCategories as $category) {
                    $entry['tags'][] = $category;
                }
            }
            if (!empty($vendorFields)) {
                $entry['_rssbridge'] = $vendorFields;
            }

            if (empty($entry['id'])) {
                $entry['id'] = hash('sha1', $entryTitle . $entryContent);
            }

            $items[] = $entry;
        }
        $data['items'] = $items;

        /**
         * The intention here is to discard non-utf8 byte sequences.
         * But the JSON_PARTIAL_OUTPUT_ON_ERROR also discards lots of other errors.
         * So consider this a hack.
         * Switch to JSON_INVALID_UTF8_IGNORE when PHP 7.2 is the latest platform requirement.
         */
        $json = json_encode($data, \JSON_PRETTY_PRINT | \JSON_PARTIAL_OUTPUT_ON_ERROR);

        return $json;
    }
}
