<?php

interface ActionInterface
{
    /**
     * @return string|Response
     */
    public function execute(Request $request);
}
