<?php

/**
 * Uses the API as documented here:
 * https://haveibeenpwned.com/API/v3#AllBreaches
 *
 * Gets the latest breaches by the date of the breach or when it was added to
 * HIBP.
 * */
class HaveIBeenPwnedBridge extends BridgeAbstract
{
    const NAME = 'Have I Been Pwned (HIBP) Bridge';
    const URI = 'https://haveibeenpwned.com';
    const DESCRIPTION = 'Returns list of Pwned websites';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [[
        'order' => [
            'name' => 'Order by',
            'type' => 'list',
            'values' => [
                'Breach date' => 'breachDate',
                'Date added to HIBP' => 'dateAdded',
            ],
            'defaultValue' => 'dateAdded',
        ],
        'item_limit' => [
            'name' => 'Limit number of returned items',
            'type' => 'number',
            'required' => true,
            'defaultValue' => 20,
        ]
    ]];
    const API_URI = 'https://haveibeenpwned.com/api/v3';

    const CACHE_TIMEOUT = 3600;

    private $breaches = [];

    public function collectData()
    {
        $data = json_decode(getContents(self::API_URI . '/breaches'), true);

        foreach ($data as $breach) {
            $item = [];

            $pwnCount = number_format($breach['PwnCount']);
            $item['title'] = $breach['Title'] . ' - '
                           . $pwnCount . ' breached accounts';
            $item['dateAdded'] = $breach['AddedDate'];
            $item['breachDate'] = $breach['BreachDate'];
            $item['uri'] = self::URI . '/breach/' . $breach['Name'];

            $item['content'] = '<p>' . $breach['Description'] . '</p>';
            $item['content'] .= '<p>' . $this->breachType($breach) . '</p>';

            $breachDate = date('j F Y', strtotime($breach['BreachDate']));
            $addedDate = date('j F Y', strtotime($breach['AddedDate']));
            $compData = implode(', ', $breach['DataClasses']);

            $item['content'] .= <<<EOD
<p>
<strong>Breach date:</strong> {$breachDate}<br>
<strong>Date added to HIBP:</strong> {$addedDate}<br>
<strong>Compromised accounts:</strong> {$pwnCount}<br>
<strong>Compromised data:</strong> {$compData}<br>
EOD;
            $item['uid'] = $breach['Name'];
            $this->breaches[] = $item;
        }

        $this->orderBreaches();
        $this->createItems();
    }

    private const BREACH_TYPES = [
        'IsVerified' => [
            false => 'Unverified breach, may be sourced from elsewhere'
        ],
        'IsFabricated' => [
            true => 'Fabricated breach, likely not legitimate'
        ],
        'IsSensitive' => [
            true => 'Sensitive breach, not publicly searchable'
        ],
        'IsRetired' => [
            true => 'Retired breach, removed from system'
        ],
        'IsSpamList' => [
            true => 'Spam list, used for spam marketing'
        ],
        'IsMalware' => [
            true => 'Malware breach'
        ],
    ];

    /**
     * Extract data breach type(s)
     */
    private function breachType($breach)
    {
        $content = '';

        foreach (self::BREACH_TYPES as $type => $message) {
            if (isset($message[$breach[$type]])) {
                $content .= $message[$breach[$type]] . '.<br>';
            }
        }

        return $content;
    }

    /**
     * Order Breaches by date added or date breached
     */
    private function orderBreaches()
    {
        $sortBy = $this->getInput('order');
        $sort = [];

        foreach ($this->breaches as $key => $item) {
            $sort[$key] = $item[$sortBy];
        }

        array_multisort($sort, SORT_DESC, $this->breaches);
    }

    /**
     * Create items from breaches array
     */
    private function createItems()
    {
        $limit = $this->getInput('item_limit');

        if ($limit < 1) {
            $limit = 20;
        }

        foreach ($this->breaches as $breach) {
            $item = [];

            $item['title'] = $breach['title'];
            $item['timestamp'] = $breach[$this->getInput('order')];
            $item['uri'] = $breach['uri'];
            $item['content'] = $breach['content'];
            $item['uid'] = $breach['uid'];

            $this->items[] = $item;

            if (count($this->items) >= $limit) {
                break;
            }
        }
    }
}
