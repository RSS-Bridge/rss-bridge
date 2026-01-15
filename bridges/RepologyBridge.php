<?php

declare(strict_types=1);

class RepologyBridge extends BridgeAbstract
{
    public const NAME = 'Repology Bridge';
    public const URI = 'https://repology.org';
    public const DESCRIPTION = 'Fetches package information from the Repology API';
    public const MAINTAINER = 'tillcash';
    public const PARAMETERS = [
        [
            'project' => [
                'name' => 'Package name',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'firefox',
            ],
            'repo' => [
                'name' => 'Repository (optional, exact match)',
                'type' => 'text',
                'required' => false,
                'exampleValue' => 'debian_13',
            ],
        ],
    ];

    public function getName()
    {
        $project = trim((string) $this->getInput('project'));
        return $project !== '' ? $project : self::NAME;
    }

    public function collectData()
    {
        $project = trim((string) $this->getInput('project'));
        $filterRepo = trim((string) ($this->getInput('repo') ?? ''));

        $apiUrl = 'https://repology.org/api/v1/project/' . urlencode($project);
        $data = Json::decode(getContents($apiUrl), true);

        if (empty($data) || !is_array($data)) {
            throwServerException('Invalid or empty response from API');
        }

        foreach ($data as $entry) {
            $repo = $entry['repo'] ?? '';
            $version = $entry['version'] ?? '';
            $srcname = $entry['srcname'] ?? '';

            if ($repo === '' || $version === '') {
                continue;
            }

            if ($filterRepo !== '' && $repo !== $filterRepo) {
                continue;
            }

            if ($filterRepo !== '') {
                $title = $srcname . ' ' . $version;
            } else {
                $title = $srcname . ' ' . $version . ' – ' . $repo;
            }

            $uri = self::URI . '/project/' . urlencode($srcname) . '/versions';

            // content
            $fields = [
                'srcname'     => 'Package',
                'version'     => 'Version',
                'origversion' => 'Original version',
                'status'      => 'Status',
                'repo'        => 'Repository',
                'subrepo'     => 'Subrepository',
            ];

            $contents = [];

            foreach ($fields as $key => $label) {
                if (!empty($entry[$key])) {
                    $contents[] = $label . ': ' . $entry[$key];
                }
            }

            $contentHtml = nl2br(
                htmlspecialchars(
                    implode("\n", $contents),
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                )
            );

            $this->items[] = [
                'title'   => $title,
                'uri'     => $uri,
                'content' => $contentHtml,
            ];
        }
    }
}
