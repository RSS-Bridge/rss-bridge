<?php

class ListAction implements ActionInterface
{
    private BridgeFactory $bridgeFactory;

    public function __construct(
        BridgeFactory $bridgeFactory
    ) {
        $this->bridgeFactory = $bridgeFactory;
    }

    public function __invoke(Request $request): Response
    {
        $list = new \stdClass();
        $list->bridges = [];
        $list->total = 0;

        foreach ($this->bridgeFactory->getBridgeClassNames() as $bridgeClassName) {
            $bridge = $this->bridgeFactory->create($bridgeClassName);

            $list->bridges[$bridgeClassName] = [
                'status'        => $this->bridgeFactory->isEnabled($bridgeClassName) ? 'active' : 'inactive',
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
