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

class ListAction implements ActionInterface
{
    private BridgeFactory $bridgeFactory;

    public function __construct(Fetcher $fetcher)
    {
        $this->bridgeFactory = new \BridgeFactory($fetcher);
    }

    public function execute(array $request)
    {
        $list = new StdClass();
        $list->bridges = [];
        $list->total = 0;

        foreach ($this->bridgeFactory->getBridgeClassNames() as $bridgeClassName) {
            $bridge = $this->bridgeFactory->create($bridgeClassName);

            if ($bridge === false) { // Broken bridge, show as inactive
                $list->bridges[$bridgeClassName] = [
                    'status' => 'inactive'
                ];

                continue;
            }

            $status = $this->bridgeFactory->isWhitelisted($bridgeClassName) ? 'active' : 'inactive';

            $list->bridges[$bridgeClassName] = [
                'status' => $status,
                'uri' => $bridge->getURI(),
                'donationUri' => $bridge->getDonationURI(),
                'name' => $bridge->getName(),
                'icon' => $bridge->getIcon(),
                'parameters' => $bridge->getParameters(),
                'maintainer' => $bridge->getMaintainer(),
                'description' => $bridge->getDescription()
            ];
        }

        $list->total = count($list->bridges);

        header('Content-Type: application/json');
        echo json_encode($list, JSON_PRETTY_PRINT);
    }
}
