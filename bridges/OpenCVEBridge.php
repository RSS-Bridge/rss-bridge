<?php

class OpenCVEBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'OpenCVE Bridge';
    const URI = 'https://opencve.io';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'A feed for search results from OpenCVE';

    const PARAMETERS = [
        '' => [
            'instance' => [
                'name' => 'OpenCVE Instance',
                'required' => true,
                'defaultValue' => 'https://app.opencve.io',
                'exampleValue' => 'https://app.opencve.io'
            ],
            'login' => [
                'name' => 'Login',
                'type' => 'text',
                'required' => true
            ],
            'password' => [
                'name' => 'Password',
                'type' => 'text',
                'required' => true
            ],
            'pages' => [
                'name' => 'Number of pages',
                'type' => 'number',
                'required' => false,
                'exampleValue' => 1,
                'defaultValue' => 1
            ],
            'filter' => [
                'name' => 'Filter',
                'type' => 'text',
                'required' => false,
                'exampleValue' => 'search:jenkins;product:gitlab,cvss:critical',
                'title' => 'Syntax: param1:value1,param2:value2;param1query2:param2query2. See https://docs.opencve.io/api/cve/ for parameters'
            ],
            'upd_timestamp' => [
                'name' => 'Use updated_at instead of created_at as timestamp',
                'type' => 'checkbox'
            ],
            'trunc_summary' => [
                'name' => 'Truncate summary for header',
                'type' => 'number',
                'defaultValue' => 100
            ],
            'fetch_contents' => [
                'name' => 'Fetch detailed contents for CVEs',
                'defaultValue' => 'checked',
                'type' => 'checkbox'
            ]
        ]
    ];

    const CSS = '
      <style>
        .cvss-na-color {
          background-color: #d2d6de;
          color: #000;
        }
        .cvss-low-color {
          background-color: #0073b7;
          color: #fff;
        }
        .cvss-medium-color {
          background-color: #f39c12;
          color: #fff;
        }
        .cvss-high-color {
          background-color: #dd4b39;
          color: #fff;
        }
        .cvss-crit-color {
          background-color: #972b1e;
          color: #fff;
        }
        .label {
          padding: .2em .6em .3em;
          font-size: 75%;
          font-weight: 700;
          line-height: 1;
          text-align: center;
          white-space: nowrap;
          border-radius: .25em;
        }
        .labels-row {
           display: flex;
           flex-direction: row;
           align-items: center;
           white-space: nowrap;
           overflow: hidden;
           margin-bottom: 6px;
        }
        .labels-row div {
           margin-right: 6px;
        }
        .cvss-table table {
           border-collapse: collapse;
           width: 100%;
           margin-bottom: 12px;
        }
        .cvss-table td, th {
           border: 1px solid #dddddd;
           text-align: left;
           padding: 8px;
        }
    </style>';

    public function collectData()
    {
        $creds = $this->getInput('login') . ':' . $this->getInput('password');
        $authHeader = 'Authorization: Basic ' . base64_encode($creds);
        $instance = $this->getInput('instance');

        $queries = [];
        $filter = $this->getInput('filter');
        $filterValues = [];
        if ($filter && mb_strlen($filter) > 0) {
            $filterValues = explode(';', $filter);
        } else {
            $queries[''] = [];
        }
        foreach ($filterValues as $filterValue) {
            $params = explode(',', $filterValue);
            $queryName = $filterValue;
            $query = [];
            foreach ($params as $param) {
                [$key, $value] = explode(':', $param);
                if ($key == 'title') {
                    $queryName = $value;
                } else {
                    $query[$key] = $value;
                }
            }
            $queries[$queryName] = $query;
        }

        $fetchedIds = [];

        foreach ($queries as $queryName => $query) {
            for ($i = 1; $i <= $this->getInput('pages'); $i++) {
                $queryPaginated = array_merge($query, ['page' => $i]);
                $url = $instance . '/api/cve?' . http_build_query($queryPaginated);

                $response = getContents($url, [$authHeader]);

                $titlePrefix = '';
                if (count($queries) > 1) {
                    $titlePrefix = '[' . $queryName . '] ';
                }

                foreach (json_decode($response)->results as $cveItem) {
                    if (array_key_exists($cveItem->cve_id, $fetchedIds)) {
                        continue;
                    }
                    $fetchedIds[$cveItem->cve_id] = true;
                    $item = [
                        'uri' => $instance . '/cve/' . $cveItem->cve_id,
                        'uid' => $cveItem->cve_id,
                    ];
                    if ($this->getInput('upd_timestamp') == 1) {
                        $item['timestamp'] = strtotime($cveItem->updated_at);
                    } else {
                        $item['timestamp'] = strtotime($cveItem->created_at);
                    }
                    if ($this->getInput('fetch_contents')) {
                        [$content, $title] = $this->fetchContents(
                            $cveItem,
                            $titlePrefix,
                            $instance,
                            $authHeader
                        );
                        $item['content'] = $content;
                        $item['title'] = $title;
                    } else {
                        $item['content'] = $cveItem->description . $this->getLinks($cveItem->cve_id);
                        $item['title'] = $this->getTitle($titlePrefix, $cveItem);
                    }
                    $this->items[] = $item;
                }
            }
        }
        usort($this->items, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
    }

    private function getTitle($titlePrefix, $cveItem)
    {
        $summary = $cveItem->description;
        $limit = $this->getInput('limit');
        if ($limit && mb_strlen($summary) > 100) {
            $summary = mb_substr($summary, 0, $limit) + '...';
        }
        return $titlePrefix . $cveItem->cve_id . '. ' . $summary;
    }

    private function fetchContents($cveItem, $titlePrefix, $instance, $authHeader)
    {
        $url = $instance . '/api/cve/' . $cveItem->cve_id;

        $response = getContents($url, [$authHeader]);
        $datum = json_decode($response);

        $title = $this->getTitleFromDatum($datum, $titlePrefix);

        $result = self::CSS;
        $result .= '<h1>' . $cveItem->cve_id . '</h1>';
        $result .= $this->getCVSSLabels($datum);
        $result .= '<p>' . $datum->description . '</p>';
        $result .= <<<EOD
            <h3>Information:</h3>
            <p>
              <ul>
                <li><b>Created At</b>: {$datum->created_at}
                <li><b>Updated At</b>: {$datum->updated_at}
              </ul>
            </p>
            EOD;

        if (isset($datum->metrics->cvssV4_0->data->vector)) {
            $result .= $this->cvssV4VectorToTable($datum->metrics->cvssV4_0->data->vector);
        }

        if (isset($datum->metrics->cvssV3_1->data->vector)) {
            $result .= $this->cvssV3VectorToTable($datum->metrics->cvssV3_1->data->vector);
        }

        if (isset($datum->metrics->cvssV3_0->data->vector)) {
            $result .= $this->cvssV3VectorToTable($datum->metrics->cvssV3_0->data->vector);
        }

        if (isset($datum->metrics->cvssV2_0->data->vector)) {
            $result .= $this->cvssV2VectorToTable($datum->metrics->cvssV2_0->data->vector);
        }

        $result .= $this->getLinks($datum->cve_id);
        $result .= $this->getVendors($datum);

        return [$result, $title];
    }

    private function getTitleFromDatum($datum, $titlePrefix)
    {
        $title = $titlePrefix;
        if (isset($datum->metrics->cvssV4_0->data->score)) {
            $title .= "[v4: {$datum->metrics->cvssV4_0->data->score}] ";
        }
        if (isset($datum->metrics->cvssV3_1->data->score)) {
            $title .= "[v3.1: {$datum->metrics->cvssV3_1->data->score}] ";
        }
        if (isset($datum->metrics->cvssV3_0->data->score)) {
            $title .= "[v3: {$datum->metrics->cvssV3_0->data->score}] ";
        }
        if (isset($datum->metrics->cvssV2_0->data->score)) {
            $title .= "[v2: {$datum->metrics->cvssV2_0->data->score}] ";
        }
        $title .= $datum->cve_id . '. ';
        $titlePostfix = $datum->description;
        $limit = $this->getInput('limit');
        if ($limit && mb_strlen($titlePostfix) > 100) {
            $titlePostfix = mb_substr($titlePostfix, 0, $limit) + '...';
        }
        $title .= $titlePostfix;
        return $title;
    }

    private function getCVSSLabels($datum)
    {
        $cvss4 = '';
        $cvss31 = '';
        $cvss3 = '';
        $cvss2 = '';
        if (isset($datum->metrics->cvssV4_0->data->score)) {
            $cvss4 = $this->formatCVSSLabel($datum->metrics->cvssV4_0->data->score, '4.0', 9, 7, 4);
        }
        if (isset($datum->metrics->cvssV3_1->data->score)) {
            $cvss31 = $this->formatCVSSLabel($datum->metrics->cvssV3_1->data->score, '3.1', 9, 7, 4);
        }
        if (isset($datum->metrics->cvssV3_0->data->score)) {
            $cvss3 = $this->formatCVSSLabel($datum->metrics->cvssV3_0->data->score, '3.0', 9, 7, 4);
        }
        if (isset($datum->metrics->cvssV2_0->data->score)) {
            $cvss2 = $this->formatCVSSLabel($datum->metrics->cvssV2_0->data->score, '2.0', 99, 7, 4);
        }

        return '<div class="labels-row">' . $cvss4 . $cvss31 . $cvss3 . $cvss2 . '</div>';
    }

    private function formatCVSSLabel($score, $version, $critical_thr, $high_thr, $medium_thr)
    {
        $text = 'n/a';
        $class = 'cvss-na-color';
        if ($score) {
            $importance = '';
            if ($score >= $critical_thr) {
                $importance = 'CRITICAL';
                $class = 'cvss-crit-color';
            } else if ($score >= $high_thr) {
                $importance = 'HIGH';
                $class = 'cvss-high-color';
            } else if ($score >= $medium_thr) {
                $importance = 'MEDIUM';
                $class = 'cvss-medium-color';
            } else {
                $importance = 'LOW';
                $class = 'cvss-low-color';
            }
            $text = sprintf('[%s] %.1f', $importance, $score);
        }
        $item = "<div>CVSS {$version}: </div><div class=\"label {$class}\">{$text}</div>";
        return $item;
    }

    private function getLinks($id)
    {
        return <<<EOD
            <h3>Links</h3>
            <p>
              <ul>
                <li>NVD Link: <a href="https://nvd.nist.gov/vuln/detail/{$id}">{$id}</a>
                <li>MITRE Link: <a href="https://cve.mitre.org/cgi-bin/cvename.cgi?name={$id}">{$id}</a>
                <li>CVE.ORG Link: <a href="https://www.cve.org/CVERecord?id={$id}">{$id}</a>
              </ul>
            </p>
            EOD;
    }

    private function cvssV3VectorToTable($cvssVector)
    {
        $vectorComponents = [];
        $parts = explode('/', $cvssVector);

        if (!preg_match('/^CVSS:3\.[01]/', $parts[0])) {
            return 'Error: Not a valid CVSS v3.0 or v3.1 vector';
        }

        for ($i = 1; $i < count($parts); $i++) {
            $component = explode(':', $parts[$i]);
            if (count($component) == 2) {
                $vectorComponents[$component[0]] = $component[1];
            }
        }

        $readableNames = [
            'AV' => ['N' => 'Network', 'A' => 'Adjacent', 'L' => 'Local', 'P' => 'Physical'],
            'AC' => ['L' => 'Low', 'H' => 'High'],
            'PR' => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'UI' => ['N' => 'None', 'R' => 'Required'],
            'S'  => ['U' => 'Unchanged', 'C' => 'Changed'],
            'C'  => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'I'  => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'A'  => ['N' => 'None', 'L' => 'Low', 'H' => 'High']
        ];

        $data = new stdClass();
        $data->attackVector = isset($readableNames['AV'][$vectorComponents['AV']]) ? $readableNames['AV'][$vectorComponents['AV']] : 'Unknown';
        $data->attackComplexity = isset($readableNames['AC'][$vectorComponents['AC']]) ? $readableNames['AC'][$vectorComponents['AC']] : 'Unknown';
        $data->privilegesRequired = isset($readableNames['PR'][$vectorComponents['PR']]) ? $readableNames['PR'][$vectorComponents['PR']] : 'Unknown';
        $data->userInteraction = isset($readableNames['UI'][$vectorComponents['UI']]) ? $readableNames['UI'][$vectorComponents['UI']] : 'Unknown';
        $data->scope = isset($readableNames['S'][$vectorComponents['S']]) ? $readableNames['S'][$vectorComponents['S']] : 'Unknown';
        $data->confidentialityImpact = isset($readableNames['C'][$vectorComponents['C']]) ? $readableNames['C'][$vectorComponents['C']] : 'Unknown';
        $data->integrityImpact = isset($readableNames['I'][$vectorComponents['I']]) ? $readableNames['I'][$vectorComponents['I']] : 'Unknown';
        $data->availabilityImpact = isset($readableNames['A'][$vectorComponents['A']]) ? $readableNames['A'][$vectorComponents['A']] : 'Unknown';

        $html = '<div class="cvss-table">
              <h3>CVSS v3 details</h3>
              <table>
                <tr>
                  <td>Attack vector</td><td>' . $data->attackVector . '</td>
                  <td>Confidentiality Impact</td><td>' . $data->confidentialityImpact . '</td>
                </tr>
                <tr>
                  <td>Attack complexity</td><td>' . $data->attackComplexity . '</td>
                  <td>Integrity Impact</td><td>' . $data->integrityImpact . '</td>
                </tr>
                <tr>
                  <td>Privileges Required</td><td>' . $data->privilegesRequired . '</td>
                  <td>Availability Impact</td><td>' . $data->availabilityImpact . '</td>
                </tr>
                <tr>
                  <td>User Interaction</td><td>' . $data->userInteraction . '</td>
                  <td>Scope</td><td>' . $data->scope . '</td>
                </tr>
              </table>
            </div>';

        return $html;
    }

    private function cvssV2VectorToTable($cvssVector)
    {
        $vectorComponents = [];
        $parts = explode('/', $cvssVector);

        foreach ($parts as $part) {
            $component = explode(':', $part);
            if (count($component) == 2) {
                $vectorComponents[$component[0]] = $component[1];
            }
        }

        $readableNames = [
            'AV' => ['L' => 'Local', 'A' => 'Adjacent Network', 'N' => 'Network'],
            'AC' => ['H' => 'High', 'M' => 'Medium', 'L' => 'Low'],
            'Au' => ['M' => 'Multiple', 'S' => 'Single', 'N' => 'None'],
            'C'  => ['N' => 'None', 'P' => 'Partial', 'C' => 'Complete'],
            'I'  => ['N' => 'None', 'P' => 'Partial', 'C' => 'Complete'],
            'A'  => ['N' => 'None', 'P' => 'Partial', 'C' => 'Complete']
        ];

        $metricValues = [
            'AV' => ['L' => 0.395, 'A' => 0.646, 'N' => 1.0],
            'AC' => ['H' => 0.35, 'M' => 0.61, 'L' => 0.71],
            'Au' => ['M' => 0.45, 'S' => 0.56, 'N' => 0.704],
            'C'  => ['N' => 0, 'P' => 0.275, 'C' => 0.660],
            'I'  => ['N' => 0, 'P' => 0.275, 'C' => 0.660],
            'A'  => ['N' => 0, 'P' => 0.275, 'C' => 0.660]
        ];

        $confImpact = isset($metricValues['C'][$vectorComponents['C']]) ? $metricValues['C'][$vectorComponents['C']] : 0;
        $integImpact = isset($metricValues['I'][$vectorComponents['I']]) ? $metricValues['I'][$vectorComponents['I']] : 0;
        $availImpact = isset($metricValues['A'][$vectorComponents['A']]) ? $metricValues['A'][$vectorComponents['A']] : 0;

        $impact = 10.41 * (1 - (1 - $confImpact) * (1 - $integImpact) * (1 - $availImpact));

        $av = isset($metricValues['AV'][$vectorComponents['AV']]) ? $metricValues['AV'][$vectorComponents['AV']] : 0;
        $ac = isset($metricValues['AC'][$vectorComponents['AC']]) ? $metricValues['AC'][$vectorComponents['AC']] : 0;
        $au = isset($metricValues['Au'][$vectorComponents['Au']]) ? $metricValues['Au'][$vectorComponents['Au']] : 0;

        $exploitability = 20 * $av * $ac * $au;

        $impact = round($impact, 1);
        $exploitability = round($exploitability, 1);

        $data = new stdClass();
        $data->accessVector = isset($readableNames['AV'][$vectorComponents['AV']]) ? $readableNames['AV'][$vectorComponents['AV']] : 'Unknown';
        $data->accessComplexity = isset($readableNames['AC'][$vectorComponents['AC']]) ? $readableNames['AC'][$vectorComponents['AC']] : 'Unknown';
        $data->authentication = isset($readableNames['Au'][$vectorComponents['Au']]) ? $readableNames['Au'][$vectorComponents['Au']] : 'Unknown';
        $data->confidentialityImpact = isset($readableNames['C'][$vectorComponents['C']]) ? $readableNames['C'][$vectorComponents['C']] : 'Unknown';
        $data->integrityImpact = isset($readableNames['I'][$vectorComponents['I']]) ? $readableNames['I'][$vectorComponents['I']] : 'Unknown';
        $data->availabilityImpact = isset($readableNames['A'][$vectorComponents['A']]) ? $readableNames['A'][$vectorComponents['A']] : 'Unknown';

        $v2 = new stdClass();
        $v2->impactScore = $impact;
        $v2->exploitabilityScore = $exploitability;

        $html = '<div class="cvss-table">
              <h3>CVSS v2 details</h3>
              <table>
                <tr>
                  <td>Impact score</td><td>' . $v2->impactScore . '</td>
                  <td>Exploitability score</td><td>' . $v2->exploitabilityScore . '</td>
                </tr>
                <tr>
                  <td>Access Vector</td><td>' . $data->accessVector . '</td>
                  <td>Confidentiality Impact</td><td>' . $data->confidentialityImpact . '</td>
                </tr>
                <tr>
                  <td>Access Complexity</td><td>' . $data->accessComplexity . '</td>
                  <td>Integrity Impact</td><td>' . $data->integrityImpact . '</td>
                </tr>
                <tr>
                  <td>Authentication</td><td>' . $data->authentication . '</td>
                  <td>Availability Impact</td><td>' . $data->availabilityImpact . '</td>
                </tr>
              </table>
            </div>';

        return $html;
    }

    private function cvssV4VectorToTable($cvssVector)
    {
        $vectorComponents = [];
        $parts = explode('/', $cvssVector);

        if (!preg_match('/^CVSS:4\.0/', $parts[0])) {
            return 'Error: Not a valid CVSS v4.0 vector';
        }

        for ($i = 1; $i < count($parts); $i++) {
            $component = explode(':', $parts[$i]);
            if (count($component) == 2) {
                $vectorComponents[$component[0]] = $component[1];
            }
        }

        $readableNames = [
            'AV' => ['N' => 'Network', 'A' => 'Adjacent', 'L' => 'Local', 'P' => 'Physical'],
            'AC' => ['L' => 'Low', 'H' => 'High'],
            'AT' => ['N' => 'None', 'P' => 'Present'],
            'PR' => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'UI' => ['N' => 'None', 'P' => 'Passive', 'A' => 'Active'],
            'VC' => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'VI' => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'VA' => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'SC' => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'SI' => ['N' => 'None', 'L' => 'Low', 'H' => 'High'],
            'SA' => ['N' => 'None', 'L' => 'Low', 'H' => 'High']
        ];

        $data = new stdClass();
        $data->attackVector = isset($readableNames['AV'][$vectorComponents['AV']]) ? $readableNames['AV'][$vectorComponents['AV']] : 'Unknown';
        $data->attackComplexity = isset($readableNames['AC'][$vectorComponents['AC']]) ? $readableNames['AC'][$vectorComponents['AC']] : 'Unknown';
        $data->privilegesRequired = isset($readableNames['PR'][$vectorComponents['PR']]) ? $readableNames['PR'][$vectorComponents['PR']] : 'Unknown';
        $data->attackRequirements = isset($readableNames['AT'][$vectorComponents['AT']]) ? $readableNames['AT'][$vectorComponents['AT']] : 'Unknown';
        $data->userInteraction = isset($readableNames['UI'][$vectorComponents['UI']]) ? $readableNames['UI'][$vectorComponents['UI']] : 'Unknown';
        $data->confidentialityImpact = isset($readableNames['VC'][$vectorComponents['VC']]) ? $readableNames['VC'][$vectorComponents['VC']] : 'Unknown';
        $data->integrityImpact = isset($readableNames['VI'][$vectorComponents['VI']]) ? $readableNames['VI'][$vectorComponents['VI']] : 'Unknown';
        $data->availabilityImpact = isset($readableNames['VA'][$vectorComponents['VA']]) ? $readableNames['VA'][$vectorComponents['VA']] : 'Unknown';
        $data->confidentialityImpactS = isset($readableNames['SC'][$vectorComponents['SC']]) ? $readableNames['SC'][$vectorComponents['SC']] : 'Unknown';
        $data->integrityImpactS = isset($readableNames['SI'][$vectorComponents['SI']]) ? $readableNames['SI'][$vectorComponents['SI']] : 'Unknown';
        $data->availabilityImpactS = isset($readableNames['SA'][$vectorComponents['SA']]) ? $readableNames['SA'][$vectorComponents['SA']] : 'Unknown';

        $html = '<div class="cvss-table">
              <h3>CVSS v4.0 details</h3>
              <table>
                <tr>
                  <td>Attack vector</td><td>' . $data->attackVector . '</td>
                  <td>Vulnerable System Confidentiality Impact</td><td>' . $data->confidentialityImpact . '</td>
                </tr>
                <tr>
                  <td>Attack complexity</td><td>' . $data->attackComplexity . '</td>
                  <td>Vulnerable System Integrity Impact</td><td>' . $data->integrityImpact . '</td>
                </tr>
                <tr>
                  <td>Privileges Required</td><td>' . $data->privilegesRequired . '</td>
                  <td>Vulnerable System Availability Impact</td><td>' . $data->availabilityImpact . '</td>
                </tr>
                <tr>
                  <td>Attack Requirements</td><td>' . $data->attackRequirements . '</td>
                  <td>Subsequent System Confidentiality Impact</td><td>' . $data->confidentialityImpactS . '</td>
                </tr>
                <tr>
                  <td>User Interaction</td><td>' . $data->userInteraction . '</td>
                  <td>Subsequent System Integrity Impact</td><td>' . $data->integrityImpactS . '</td>
                </tr>
                <tr>
                  <td></td><td></td>
                  <td>Subsequent System Avaliablity Impact</td><td>' . $data->availabilityImpactS . '</td>
                </tr>
              </table>
            </div>';

        return $html;
    }


    private function getVendors($datum)
    {
        if (count((array)$datum->vendors) == 0) {
            return '';
        }

        $vendor_data = [];
        foreach ($datum->vendors as $vendor_str) {
            $pieces = explode('$PRODUCT$', $vendor_str);
            if (count($pieces) == 1) {
                $vendor = $pieces[0];
                if (!array_key_exists($vendor, $vendor_data)) {
                    $vendor_data[$vendor] = [];
                }
            } else {
                $vendor = $pieces[0];
                $product = $pieces[1];
                if (!array_key_exists($vendor, $vendor_data)) {
                    $vendor_data[$vendor] = [];
                }
                array_push($vendor_data[$vendor], $product);
            }
        }

        $res = '<h3>Affected products</h3><p><ul>';
        foreach ($vendor_data as $vendor => $products) {
            $res .= "<li>{$vendor}";
            if (count($products) > 0) {
                $res .= '<ul>';
                foreach ($products as $product) {
                    $res .= '<li>' . $product . '</li>';
                }
                $res .= '</ul>';
            }
            $res .= '</li>';
        }
        $res .= '</ul></p>';
        return $res;
    }
}
