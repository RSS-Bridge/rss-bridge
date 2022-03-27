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
 * An abstract class for action objects
 */
abstract class ActionAbstract implements ActionInterface
{
    /**
     * Holds the user data.
     *
     * @var array
     */
    protected $userData = null;

    /**
     * {@inheritdoc}
     *
     * @param array $userData {@inheritdoc}
     */
    public function setUserData($userData)
    {
        $this->userData = $userData;
    }
}
