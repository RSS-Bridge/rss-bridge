<?php

final class FrontpageAction implements ActionInterface
{
    public function __invoke(Request $request): Response
    {
        $messages = [];
        $activeBridges = 0;

        $bridgeFactory = new BridgeFactory();
        $bridgeClassNames = $bridgeFactory->getBridgeClassNames();

        foreach ($bridgeFactory->getMissingEnabledBridges() as $missingEnabledBridge) {
            $messages[] = [
                'body' => sprintf('Warning : Bridge "%s" not found', $missingEnabledBridge),
                'level' => 'warning'
            ];
        }

        $body = '';
        foreach ($bridgeClassNames as $bridgeClassName) {
            if ($bridgeFactory->isEnabled($bridgeClassName)) {
                $body .= BridgeCard::render($bridgeClassName, $request);
                $activeBridges++;
            }
        }

        // todo: cache this renderered template?
        return new Response(render(__DIR__ . '/../templates/frontpage.html.php', [
            'messages' => $messages,
            'admin_email' => Configuration::getConfig('admin', 'email'),
            'admin_telegram' => Configuration::getConfig('admin', 'telegram'),
            'bridges' => $body,
            'active_bridges' => $activeBridges,
            'total_bridges' => count($bridgeClassNames),
        ]));
    }
}
