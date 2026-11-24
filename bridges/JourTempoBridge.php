<?php

declare(strict_types=1);

class JourTempoBridge extends BridgeAbstract
{
    const NAME = 'Jour Tempo : jours EJP et jours PP';
    const URI = 'https://jourtempo.fr/ejp/ejp-demain/';
    const DESCRIPTION = 'EDF EJP days and RTE peak days (PP) in France: today and tomorrow, from the free Jour Tempo API';
    const MAINTAINER = 'jourtempo';
    const CACHE_TIMEOUT = 3600; // 1h
    const API = 'https://jourtempo.fr/api/v1/';
    const PARAMETERS = [
        [
            'signal' => [
                'name' => 'Signal',
                'type' => 'list',
                'values' => [
                    'Jours EJP (EDF)' => 'ejp',
                    'Jours PP (RTE, jours rouges Tempo depuis novembre 2026)' => 'pp',
                ],
                'defaultValue' => 'ejp',
            ],
        ],
    ];

    const PAGES = [
        'ejp' => 'https://jourtempo.fr/ejp/ejp-demain/',
        'pp' => 'https://jourtempo.fr/couleurs/jours-pp1-pp2/',
    ];

    public function collectData()
    {
        $signal = $this->getInput('signal') ?? 'ejp';
        $json = getContents(self::API . $signal . '.json', ['Accept: application/json']);
        $data = Json::decode($json);

        foreach (['aujourdhui' => 'Aujourd\'hui', 'demain' => 'Demain'] as $key => $label) {
            $day = $data[$key] ?? null;
            if (!is_array($day) || !isset($day['date'], $day['code'])) {
                continue;
            }
            $title = sprintf('%s %s : %s', $label, $this->formatDate($day['date']), $day['libelle'] ?? $day['code']);
            $content = '<p>' . htmlspecialchars($title) . '</p>';
            if (!empty($day['detail'])) {
                $content .= '<p>' . htmlspecialchars($day['detail']) . '</p>';
            }
            if (!empty($data['attribution'])) {
                $content .= '<p><small>' . htmlspecialchars($data['attribution']) . '</small></p>';
            }
            $this->items[] = [
                'uri' => self::PAGES[$signal],
                'title' => $title,
                'content' => $content,
                'timestamp' => strtotime($day['date']),
                'uid' => $signal . '-' . $day['date'] . '-' . $day['code'],
            ];
        }
    }

    public function getName()
    {
        if ($this->getInput('signal') === 'pp') {
            return 'Jour Tempo : jours PP';
        }
        return parent::getName();
    }

    public function getURI()
    {
        return self::PAGES[$this->getInput('signal') ?? 'ejp'] ?? self::URI;
    }

    private function formatDate(string $date): string
    {
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Paris');
        $formatter->setPattern('EEEE d MMMM yyyy');
        return $formatter->format(new DateTime($date, new DateTimeZone('Europe/Paris')));
    }
}
