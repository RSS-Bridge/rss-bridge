<?php

final class FrontpageAction implements ActionInterface
{
    public function execute(array $request)
    {
        $messages = [];
        $showInactive = (bool) ($request['show_inactive'] ?? null);
        $activeBridges = 0;

        $bridgeFactory = new BridgeFactory();
        $bridgeClassNames = $bridgeFactory->getBridgeClassNames();

        foreach ($bridgeFactory->getMissingEnabledBridges() as $missingEnabledBridge) {
            $messages[] = [
                'body' => sprintf('Warning : Bridge "%s" not found', $missingEnabledBridge),
                'level' => 'warning'
            ];
        }

        $formatFactory = new FormatFactory();
        $formats = $formatFactory->getFormatNames();

        $body = '';
        foreach ($bridgeClassNames as $bridgeClassName) {
            if ($bridgeFactory->isEnabled($bridgeClassName)) {
                $body .= BridgeCard::displayBridgeCard($bridgeClassName, $formats);
                $activeBridges++;
            } elseif ($showInactive) {
                $body .= BridgeCard::displayBridgeCard($bridgeClassName, $formats, false) . PHP_EOL;
            }
        }

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
