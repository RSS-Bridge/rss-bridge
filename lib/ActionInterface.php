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

/**
 * Interface for action objects.
 */
interface ActionInterface
{
    /**
     * Execute the action.
     *
     * Note: This function directly outputs data to the user.
     *
     * @return void
     */
    public function execute(array $request);
}
