<?php

class DetectAction implements ActionInterface
{
    private BridgeFactory $bridgeFactory;

    public function __construct(
        BridgeFactory $bridgeFactory
    ) {
        $this->bridgeFactory = $bridgeFactory;
    }

    public function __invoke(Request $request): Response
    {
        $url = $request->get('url');
        $format = $request->get('format');

        if (!$url) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'You must specify a url']));
        }
        if (!$format) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'You must specify a format']));
        }

        foreach ($this->bridgeFactory->getBridgeClassNames() as $bridgeClassName) {
            if (!$this->bridgeFactory->isEnabled($bridgeClassName)) {
                continue;
            }

            $bridge = $this->bridgeFactory->create($bridgeClassName);

            $bridgeParams = $bridge->detectParameters($url);

            if (!$bridgeParams) {
                continue;
            }

            $query = [
                'action' => 'display',
                'bridge' => $bridgeClassName,
                'format' => $format,
            ];
            $query = array_merge($query, $bridgeParams);
            return new Response('', 301, ['location' => '?' . http_build_query($query)]);
        }

        return new Response(render(__DIR__ . '/../templates/error.html.php', [
            'message' => 'No bridge found for given URL: ' . $url,
        ]));
    }
}
