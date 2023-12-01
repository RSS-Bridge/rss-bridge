<?php

/**
 * Checks if the website for a given bridge is reachable.
 *
 * **Remarks**
 * - This action is only available in debug mode.
 * - Returns the bridge status as Json-formatted string.
 * - Returns an error if the bridge is not whitelisted.
 * - Returns a responsive web page that automatically checks all whitelisted
 * bridges (using JavaScript) if no bridge is specified.
 */
class ConnectivityAction implements ActionInterface
{
    private BridgeFactory $bridgeFactory;

    public function __construct()
    {
        $this->bridgeFactory = new BridgeFactory();
    }

    public function execute(array $request)
    {
        if (!Debug::isEnabled()) {
            return new Response('This action is only available in debug mode!', 403);
        }

        $bridgeName = $request['bridge'] ?? null;
        if (!$bridgeName) {
            return render_template('connectivity.html.php');
        }
        $bridgeClassName = $this->bridgeFactory->createBridgeClassName($bridgeName);
        if (!$bridgeClassName) {
            return new Response('Bridge not found', 404);
        }
        return $this->reportBridgeConnectivity($bridgeClassName);
    }

    private function reportBridgeConnectivity($bridgeClassName)
    {
        if (!$this->bridgeFactory->isEnabled($bridgeClassName)) {
            throw new \Exception('Bridge is not whitelisted!');
        }

        $bridge = $this->bridgeFactory->create($bridgeClassName);
        $curl_opts = [
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
        ];
        $result = [
            'bridge'        => $bridgeClassName,
            'successful'    => false,
            'http_code'     => null,
        ];
        try {
            $response = getContents($bridge::URI, [], $curl_opts, true);
            $result['http_code'] = $response['code'];
            if (in_array($response['code'], [200])) {
                $result['successful'] = true;
            }
        } catch (\Exception $e) {
        }

        return new Response(Json::encode($result), 200, ['content-type' => 'text/json']);
    }
}
