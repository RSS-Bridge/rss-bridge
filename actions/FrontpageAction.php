<?php

final class FrontpageAction implements ActionInterface
{
    public function execute(Request $request)
    {
        $showInactive = (bool) $request->get('show_inactive');

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
                $body .= BridgeCard::render($bridgeClassName);
                $activeBridges++;
            } elseif ($showInactive) {
                $body .= BridgeCard::render($bridgeClassName, false) . "\n";
            }
        }

        // todo: cache this renderered template?
        return render(__DIR__ . '/../templates/frontpage.html.php', [
            'messages' => $messages,
            'admin_email' => Configuration::getConfig('admin', 'email'),
            'admin_telegram' => Configuration::getConfig('admin', 'telegram'),
            'bridges' => $body,
            'active_bridges' => $activeBridges,
            'total_bridges' => count($bridgeClassNames),
            'show_inactive' => $showInactive,
        ]);
    }
}
