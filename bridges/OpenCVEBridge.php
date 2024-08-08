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
                'defaultValue' => 'https://www.opencve.io',
                'exampleValue' => 'https://www.opencve.io'
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

                foreach (json_decode($response) as $cveItem) {
                    if (array_key_exists($cveItem->id, $fetchedIds)) {
                        continue;
                    }
                    $fetchedIds[$cveItem->id] = true;
                    $item = [
                        'uri' => $instance . '/cve/' . $cveItem->id,
                        'uid' => $cveItem->id,
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
                        $item['content'] = $cveItem->summary . $this->getLinks($cveItem->id);
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
        $summary = $cveItem->summary;
        $limit = $this->getInput('limit');
        if ($limit && mb_strlen($summary) > 100) {
            $summary = mb_substr($summary, 0, $limit) + '...';
        }
        return $titlePrefix . $cveItem->id . '. ' . $summary;
    }

    private function fetchContents($cveItem, $titlePrefix, $instance, $authHeader)
    {
        $url = $instance . '/api/cve/' . $cveItem->id;

        $response = getContents($url, [$authHeader]);
        $datum = json_decode($response);

        $title = $this->getTitleFromDatum($datum, $titlePrefix);

        $result = self::CSS;
        $result .= '<h1>' . $cveItem->id . '</h1>';
        $result .= $this->getCVSSLabels($datum);
        $result .= '<p>' . $datum->summary . '</p>';
        $result .= <<<EOD
            <h3>Information:</h3>
            <p>
              <ul>
                <li><b>Publication date</b>: {$datum->raw_nvd_data->published}
                <li><b>Last modified</b>: {$datum->raw_nvd_data->lastModified}
                <li><b>Last modified</b>: {$datum->raw_nvd_data->lastModified}
              </ul>
            </p>
            EOD;

        $result .= $this->getV3Table($datum);
        $result .= $this->getV2Table($datum);

        $result .= $this->getLinks($datum->id);
        $result .= $this->getReferences($datum);

        $result .= $this->getVendors($datum);

        return [$result, $title];
    }

    private function getTitleFromDatum($datum, $titlePrefix)
    {
        $title = $titlePrefix;
        if ($datum->cvss->v3) {
            $title .= "[v3: {$datum->cvss->v3}] ";
        }
        if ($datum->cvss->v2) {
            $title .= "[v2: {$datum->cvss->v2}] ";
        }
        $title .= $datum->id . '. ';
        $titlePostfix = $datum->summary;
        $limit = $this->getInput('limit');
        if ($limit && mb_strlen($titlePostfix) > 100) {
            $titlePostfix = mb_substr($titlePostfix, 0, $limit) + '...';
        }
        $title .= $titlePostfix;
        return $title;
    }

    private function getCVSSLabels($datum)
    {
        $CVSSv2Text = 'n/a';
        $CVSSv2Class = 'cvss-na-color';
        if ($datum->cvss->v2) {
            $importance = '';
            if ($datum->cvss->v2 >= 7) {
                $importance = 'HIGH';
                $CVSSv2Class = 'cvss-high-color';
            } else if ($datum->cvss->v2 >= 4) {
                $importance = 'MEDIUM';
                $CVSSv2Class = 'cvss-medium-color';
            } else {
                $importance = 'LOW';
                $CVSSv2Class = 'cvss-low-color';
            }
            $CVSSv2Text = sprintf('[%s] %.1f', $importance, $datum->cvss->v2);
        }
        $CVSSv2Item = "<div>CVSS v2: </div><div class=\"label {$CVSSv2Class}\">{$CVSSv2Text}</div>";

        $CVSSv3Text = 'n/a';
        $CVSSv3Class = 'cvss-na-color';
        if ($datum->cvss->v3) {
            $importance = '';
            if ($datum->cvss->v3 >= 9) {
                $importance = 'CRITICAL';
                $CVSSv3Class = 'cvss-crit-color';
            } else if ($datum->cvss->v3 >= 7) {
                $importance = 'HIGH';
                $CVSSv3Class = 'cvss-high-color';
            } else if ($datum->cvss->v3 >= 4) {
                $importance = 'MEDIUM';
                $CVSSv3Class = 'cvss-medium-color';
            } else {
                $importance = 'LOW';
                $CVSSv3Class = 'cvss-low-color';
            }
            $CVSSv3Text = sprintf('[%s] %.1f', $importance, $datum->cvss->v3);
        }
        $CVSSv3Item = "<div>CVSS v3: </div><div class=\"label {$CVSSv3Class}\">{$CVSSv3Text}</div>";
        return '<div class="labels-row">' . $CVSSv3Item . $CVSSv2Item . '</div>';
    }

    private function getReferences($datum)
    {
        if (count($datum->raw_nvd_data->references) == 0) {
            return '';
        }
        $res = '<h3>References:</h3> <p><ul>';
        foreach ($datum->raw_nvd_data->references as $ref) {
            $item = '<li>';
            if (isset($ref->tags) && count($ref->tags) > 0) {
                $item .= '[' . implode(', ', $ref->tags) . '] ';
            }
            $item .= "<a href=\"{$ref->url}\">{$ref->url}</a>";
            $item .= '<li>';
            $res .= $item;
        }
        $res .= '</p></ul>';
        return $res;
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

    private function getV3Table($datum)
    {
        $metrics = $datum->raw_nvd_data->metrics;
        if (!isset($metrics->cvssMetricV31) || count($metrics->cvssMetricV31) == 0) {
            return '';
        }
        $v3 = $metrics->cvssMetricV31[0];
        $data = $v3->cvssData;
        return <<<EOD
            <div class="cvss-table">
              <h3>CVSS v3 details</h3>
              <table>
                <tr>
                  <td>Impact score</td><td>{$v3->impactScore}</td>
                  <td>Exploitability score</td><td>{$v3->exploitabilityScore}</td>
                </tr>
                <tr>
                  <td>Attack vector</td><td>{$data->attackVector}</td>
                  <td>Confidentiality Impact</td><td>{$data->confidentialityImpact}</td>
                </tr>
                <tr>
                  <td>Attack complexity</td><td>{$data->attackComplexity}</td>
                  <td>Integrity Impact</td><td>{$data->integrityImpact}</td>
                </tr>
                <tr>
                  <td>Privileges Required</td><td>{$data->privilegesRequired}</td>
                  <td>Availability Impact</td><td>{$data->availabilityImpact}</td>
                </tr>
                <tr>
                  <td>User Interaction</td><td>{$data->userInteraction}</td>
                  <td>Scope</td><td>{$data->scope}</td>
                </tr>
              </table>
            </div>
        EOD;
    }

    private function getV2Table($datum)
    {
        $metrics = $datum->raw_nvd_data->metrics;
        if (!isset($metrics->cvssMetricV2) || count($metrics->cvssMetricV2) == 0) {
            return '';
        }
        $v2 = $metrics->cvssMetricV2[0];
        $data = $v2->cvssData;
        return <<<EOD
            <div class="cvss-table">
              <h3>CVSS v2 details</h3>
              <table>
                <tr>
                  <td>Impact score</td><td>{$v2->impactScore}</td>
                  <td>Exploitability score</td><td>{$v2->exploitabilityScore}</td>
                </tr>
                <tr>
                  <td>Access Vector</td><td>{$data->accessVector}</td>
                  <td>Confidentiality Impact</td><td>{$data->confidentialityImpact}</td>
                </tr>
                <tr>
                  <td>Access Complexity</td><td>{$data->accessComplexity}</td>
                  <td>Integrity Impact</td><td>{$data->integrityImpact}</td>
                </tr>
                <tr>
                  <td>Authentication</td><td>{$data->authentication}</td>
                  <td>Availability Impact</td><td>{$data->availabilityImpact}</td>
                </tr>
                <tr>
              </table>
            </div>
        EOD;
    }

    private function getVendors($datum)
    {
        if (count((array)$datum->vendors) == 0) {
            return '';
        }
        $res = '<h3>Affected products</h3><p><ul>';
        foreach ($datum->vendors as $vendor => $products) {
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
    }
}
