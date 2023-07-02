<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

class FindfeedAction implements ActionInterface
{
    public function execute(array $request)
    {
        $targetURL = $request['url'] ?? null;
        $format = $request['format'] ?? null;

        if (!$targetURL) {
            throw new \Exception('You must specify a url!');
        }
        if (!$format) {
            throw new \Exception('You must specify a format!');
        }
        $results = [];
        $bridgeFactory = new BridgeFactory();

        foreach ($bridgeFactory->getBridgeClassNames() as $bridgeClassName) {
            if (!$bridgeFactory->isEnabled($bridgeClassName)) {
                continue;
            }

            $bridge = $bridgeFactory->create($bridgeClassName);

            $bridgeParams = $bridge->detectParameters($targetURL);

            if (is_null($bridgeParams)) {
                continue;
            }


            $bridgeData = [];
            // Construct the array of parameters
            foreach ($bridgeParams as $key => $value) {
                // 'context' is a special case : it's a bridge parameters, there is no "name" for this parameter
                if ($key == 'context') {
                    $bridgeData[$key]['name'] = 'Context';
                    $bridgeData[$key]['value'] = $value;
                } else {
                    $bridgeData[$key]['name'] = $bridge::PARAMETERS[$bridgeParams['context']][$key]['name'];
                    $bridgeData[$key]['value'] = $value;
                }
            }


            $bridgeParams['bridge'] = $bridgeClassName;
            $bridgeParams['format'] = $format;
            $content = [
                'url' => get_home_page_url() . '?action=display&' . http_build_query($bridgeParams),
                'bridgeParams' => $bridgeParams,
                'bridgeData' => $bridgeData,
                'bridgeMeta' => [
                        'name' => $bridge::NAME,
                        'description' => $bridge::DESCRIPTION,
                        'parameters' => $bridge::PARAMETERS,
                        'icon' => $bridge->getIcon(),
                    ],
            ];
            $results[] = $content;
        }
        if (count($results) >= 1) {
            return new Response(Json::encode($results), 200, ['Content-Type' => 'application/json']);
        } else {
            throw new \Exception('No bridge found for given URL: ' . $targetURL);
        }
    }
}
