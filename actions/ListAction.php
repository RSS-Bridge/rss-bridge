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
    public function execute(array $request)
    {
        $list = new \stdClass();
        $list->bridges = [];
        $list->total = 0;

        $bridgeFactory = new BridgeFactory();

        foreach ($bridgeFactory->getBridgeClassNames() as $bridgeClassName) {
            $bridge = $bridgeFactory->create($bridgeClassName);

            $list->bridges[$bridgeClassName] = [
                'status' => $bridgeFactory->isWhitelisted($bridgeClassName) ? 'active' : 'inactive',
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
        print Json::encode($list);
    }
}
