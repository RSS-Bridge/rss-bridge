<?php

final class FrontpageAction implements ActionInterface
{
    private BridgeFactory $bridgeFactory;

    public function __construct(
        BridgeFactory $bridgeFactory
    ) {
        $this->bridgeFactory = $bridgeFactory;
    }

    public function __invoke(Request $request): Response
    {
        $token = $request->getAttribute('token');

        $messages = [];
        $activeBridges = 0;

        $bridgeClassNames = $this->bridgeFactory->getBridgeClassNames();

        foreach ($this->bridgeFactory->getMissingEnabledBridges() as $missingEnabledBridge) {
            $messages[] = [
                'body' => sprintf('Warning : Bridge "%s" not found', $missingEnabledBridge),
                'level' => 'warning'
            ];
        }

        $body = '';
        foreach ($bridgeClassNames as $bridgeClassName) {
            if ($this->bridgeFactory->isEnabled($bridgeClassName)) {
                $body .= BridgeCard::render($this->bridgeFactory, $bridgeClassName, $token);
                $activeBridges++;
            }
        }

        $response = new Response(render(__DIR__ . '/../templates/frontpage.html.php', [
            'messages'          => $messages,
            'admin_email'       => Configuration::getConfig('admin', 'email'),
            'admin_telegram'    => Configuration::getConfig('admin', 'telegram'),
            'bridges'           => $body,
            'active_bridges'    => $activeBridges,
            'total_bridges'     => count($bridgeClassNames),
        ]));

        // TODO: The rendered template could be cached, but beware config changes that changes the html
        return $response;
    }
}
