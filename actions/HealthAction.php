<?php

declare(strict_types=1);

class HealthAction implements ActionInterface
{
    public function __invoke(Request $request): Response
    {
        $response = [
            'code' => 200,
            'message' => 'all is good',
        ];
        return new Response(Json::encode($response), 200, ['content-type' => 'application/json']);
    }
}
