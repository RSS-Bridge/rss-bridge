<?php

/**
 * This action is used by the frontpage form search.
 * It finds a bridge based off of a user input url.
 * It uses bridges' detectParameters implementation.
 */
class FindfeedAction implements ActionInterface
{
    public function execute(Request $request)
    {
        $url = $request->get('url');
        $format = $request->get('format');

        if (!$url) {
            return new Response('You must specify a url', 400);
        }
        if (!$format) {
            return new Response('You must specify a format', 400);
        }

        $bridgeFactory = new BridgeFactory();

        $results = [];
        foreach ($bridgeFactory->getBridgeClassNames() as $bridgeClassName) {
            if (!$bridgeFactory->isEnabled($bridgeClassName)) {
                continue;
            }

            $bridge = $bridgeFactory->create($bridgeClassName);

            $bridgeParams = $bridge->detectParameters($url);

            if ($bridgeParams === null) {
                continue;
            }

            // It's allowed to have no 'context' in a bridge (only a default context without any name)
            // In this case, the reference to the parameters are found in the first element of the PARAMETERS array

            $context = $bridgeParams['context'] ?? 0;

            $bridgeData = [];
            // Construct the array of parameters
            foreach ($bridgeParams as $key => $value) {
                // 'context' is a special case : it's a bridge parameters, there is no "name" for this parameter
                if ($key == 'context') {
                    $bridgeData[$key]['name'] = 'Context';
                    $bridgeData[$key]['value'] = $value;
                } else {
                    $bridgeData[$key]['name'] = $this->getParameterName($bridge, $context, $key);
                    $bridgeData[$key]['value'] = $value;
                }
            }

            $bridgeParams['bridge'] = $bridgeClassName;
            $bridgeParams['format'] = $format;
            $content = [
                'url' => './?action=display&' . http_build_query($bridgeParams),
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
        if ($results === []) {
            return new Response(Json::encode(['message' => 'No bridge found for given url']), 404, ['content-type' => 'application/json']);
        }
        return new Response(Json::encode($results), 200, ['content-type' => 'application/json']);
    }

    // Get parameter name in the actual context, or in the global parameter
    private function getParameterName($bridge, $context, $key)
    {
        if (isset($bridge::PARAMETERS[$context][$key]['name'])) {
            $name = $bridge::PARAMETERS[$context][$key]['name'];
        } else if (isset($bridge::PARAMETERS['global'][$key]['name'])) {
            $name = $bridge::PARAMETERS['global'][$key]['name'];
        } else {
            $name = 'Variable "' . $key . '" (No name provided)';
        }
        return $name;
    }
}
