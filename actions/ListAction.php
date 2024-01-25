<?php

class ListAction implements ActionInterface
{
    public function execute(Request $request)
    {
        $list = new \stdClass();
        $list->bridges = [];
        $list->total = 0;

        $bridgeFactory = new BridgeFactory();

        foreach ($bridgeFactory->getBridgeClassNames() as $bridgeClassName) {
            $bridge = $bridgeFactory->create($bridgeClassName);

            $list->bridges[$bridgeClassName] = [
                'status'        => $bridgeFactory->isEnabled($bridgeClassName) ? 'active' : 'inactive',
                'uri'           => $bridge->getURI(),
                'donationUri'   => $bridge->getDonationURI(),
                'name'          => $bridge->getName(),
                'icon'          => $bridge->getIcon(),
                'parameters'    => $bridge->getParameters(),
                'maintainer'    => $bridge->getMaintainer(),
                'description'   => $bridge->getDescription()
            ];
        }
        $list->total = count($list->bridges);
        return new Response(Json::encode($list), 200, ['content-type' => 'application/json']);
    }
}
