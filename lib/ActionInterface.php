<?php

interface ActionInterface
{
    public function __invoke(Request $request): Response;
}
