<?php

declare(strict_types=1);

class TestAction implements ActionInterface
{
    public function __construct()
    {
    }

    public function __invoke(Request $request): Response
    {
        return new Response('This is a test');
    }
}
