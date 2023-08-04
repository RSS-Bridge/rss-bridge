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
 * The bridge interface
 *
 * A bridge is a class that is responsible for collecting and transforming data
 * from one hosting provider into an internal representation of feed data, that
 * can later be transformed into different feed formats (see {@see FormatInterface}).
 *
 * For this purpose, all bridges need to perform three common operations:
 *
 * 1. Collect data from a remote site.
 * 2. Extract the required contents.
 * 3. Add the contents to the internal data structure.
 *
 * Bridges can optionally specify parameters to customize bridge behavior based
 * on user input. For example, a user could specify how many items to return in
 * the feed and where to get them.
 *
 * In order to present a bridge on the home page, and for the purpose of bridge
 * specific behaviour, additional information must be provided by the bridge:
 *
 * * **Name**
 * The name of the bridge that can be displayed to users.
 *
 * * **Description**
 * A brief description for the bridge that can be displayed to users.
 *
 * * **URI**
 * A link to the hosting provider.
 *
 * * **Maintainer**
 * The GitHub username of the bridge maintainer
 *
 * * **Parameters**
 * A list of parameters for customization
 *
 * * **Icon**
 * A link to the favicon of the hosting provider
 *
 * * **Cache timeout**
 * The default cache timeout for the bridge.
 */
interface BridgeInterface
{
    /**
     * Collects data from the site
     *
     * @return void
     */
    public function collectData();

    /**
     * Returns the value for the selected configuration
     *
     * @param string $input The option name
     * @return mixed|null The option value or null if the input is not defined
     */
    public function getOption($name);

    /**
     * Returns the description
     *
     * @return string Description
     */
    public function getDescription();

    /**
     * Returns an array of collected items
     *
     * @return array Associative array of items
     */
    public function getItems();

    /**
     * Returns the bridge maintainer
     *
     * @return string Bridge maintainer
     */
    public function getMaintainer();

    /**
     * Returns the bridge name
     *
     * @return string Bridge name
     */
    public function getName();

    /**
     * Returns the bridge icon
     *
     * @return string Bridge icon
     */
    public function getIcon();

    /**
     * Returns the bridge parameters
     *
     * @return array Bridge parameters
     */
    public function getParameters();

    /**
     * Returns the bridge URI
     *
     * @return string Bridge URI
     */
    public function getURI();

    /**
     * Returns the bridge Donation URI
     *
     * @return string Bridge Donation URI
     */
    public function getDonationURI();

    /**
     * Returns the cache timeout
     *
     * @return int Cache timeout
     */
    public function getCacheTimeout();

    /**
     * Returns parameters from given URL or null if URL is not applicable
     *
     * @param string $url URL to extract parameters from
     * @return array|null List of bridge parameters or null if detection failed.
     */
    public function detectParameters($url);

    public function getShortName(): string;
}
