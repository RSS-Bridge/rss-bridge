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

abstract class BridgeAbstract implements BridgeInterface
{
    /**
     * Name of the bridge
     *
     * Use {@see BridgeAbstract::getName()} to read this parameter
     */
    const NAME = 'Unnamed bridge';

    /**
     * URI to the site the bridge is intended to be used for.
     *
     * Use {@see BridgeAbstract::getURI()} to read this parameter
     */
    const URI = '';

    /**
     * Donation URI to the site the bridge is intended to be used for.
     *
     * Use {@see BridgeAbstract::getDonationURI()} to read this parameter
     */
    const DONATION_URI = '';

    /**
     * A brief description of what the bridge can do
     *
     * Use {@see BridgeAbstract::getDescription()} to read this parameter
     */
    const DESCRIPTION = 'No description provided';

    /**
     * The name of the maintainer. Multiple maintainers can be separated by comma
     *
     * Use {@see BridgeAbstract::getMaintainer()} to read this parameter
     */
    const MAINTAINER = 'No maintainer';

    /**
     * The default cache timeout for the bridge
     *
     * Use {@see BridgeAbstract::getCacheTimeout()} to read this parameter
     */
    const CACHE_TIMEOUT = 3600;

    /**
     * Configuration for the bridge
     */
    const CONFIGURATION = [];

    /**
     * Parameters for the bridge
     *
     * Use {@see BridgeAbstract::getParameters()} to read this parameter
     */
    const PARAMETERS = [];

    /**
     * Test cases for detectParameters for the bridge
     */
    const TEST_DETECT_PARAMETERS = [];

    /**
     * This is a convenient const for the limit option in bridge contexts.
     * Can be inlined and modified if necessary.
     */
    protected const LIMIT = [
        'name'          => 'Limit',
        'type'          => 'number',
        'title'         => 'Maximum number of items to return',
    ];

    /**
     * Holds the list of items collected by the bridge
     *
     * Items must be collected by {@see BridgeInterface::collectData()}
     *
     * Use {@see BridgeAbstract::getItems()} to access items.
     *
     * @var array
     */
    protected array $items = [];

    /**
     * Holds the list of input parameters used by the bridge
     *
     * Do not access this parameter directly!
     * Use {@see BridgeAbstract::setInputs()} and {@see BridgeAbstract::getInput()} instead!
     *
     * @var array
     */
    protected array $inputs = [];

    /**
     * Holds the name of the queried context
     *
     * @var string
     */
    protected $queriedContext = '';

    /**
     * Holds the list of bridge-specific configurations from config.ini.php, used by the bridge.
     */
    private array $configuration = [];

    public function __construct()
    {
    }

    /** {@inheritdoc} */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Sets the input values for a given context.
     *
     * @param array $inputs Associative array of inputs
     * @param string $queriedContext The context name
     * @return void
     */
    protected function setInputs(array $inputs, $queriedContext)
    {
        // Import and assign all inputs to their context
        foreach ($inputs as $name => $value) {
            foreach (static::PARAMETERS as $context => $set) {
                if (array_key_exists($name, static::PARAMETERS[$context])) {
                    $this->inputs[$context][$name]['value'] = $value;
                }
            }
        }

        // Apply default values to missing data
        $contexts = [$queriedContext];
        if (array_key_exists('global', static::PARAMETERS)) {
            $contexts[] = 'global';
        }

        foreach ($contexts as $context) {
            if (!isset(static::PARAMETERS[$context])) {
                // unknown context provided by client, throw exception here? or continue?
            }

            foreach (static::PARAMETERS[$context] as $name => $properties) {
                if (isset($this->inputs[$context][$name]['value'])) {
                    continue;
                }

                $type = $properties['type'] ?? 'text';

                switch ($type) {
                    case 'checkbox':
                        $this->inputs[$context][$name]['value'] = $inputs[$context][$name]['value'] ?? false;
                        break;
                    case 'list':
                        if (!isset($properties['defaultValue'])) {
                            $firstItem = reset($properties['values']);
                            if (is_array($firstItem)) {
                                $firstItem = reset($firstItem);
                            }
                            $this->inputs[$context][$name]['value'] = $firstItem;
                        } else {
                            $this->inputs[$context][$name]['value'] = $properties['defaultValue'];
                        }
                        break;
                    default:
                        if (isset($properties['defaultValue'])) {
                            $this->inputs[$context][$name]['value'] = $properties['defaultValue'];
                        }
                        break;
                }
            }
        }

        // Copy global parameter values to the guessed context
        if (array_key_exists('global', static::PARAMETERS)) {
            foreach (static::PARAMETERS['global'] as $name => $properties) {
                if (isset($inputs[$name])) {
                    $value = $inputs[$name];
                } else {
                    if ($properties['type'] === 'checkbox') {
                        $value = false;
                    } elseif (isset($properties['defaultValue'])) {
                        $value = $properties['defaultValue'];
                    } else {
                        continue;
                    }
                }
                $this->inputs[$queriedContext][$name]['value'] = $value;
            }
        }

        // Only keep guessed context parameters values
        if (isset($this->inputs[$queriedContext])) {
            $this->inputs = [$queriedContext => $this->inputs[$queriedContext]];
        } else {
            $this->inputs = [];
        }
    }

    /**
     * Set inputs for the bridge
     *
     * Returns errors and aborts execution if the provided input parameters are
     * invalid.
     *
     * @param array List of input parameters. Each element in this list must
     * relate to an item in {@see BridgeAbstract::PARAMETERS}
     * @return void
     */
    public function setDatas(array $inputs)
    {
        if (isset($inputs['context'])) { // Context hinting (optional)
            $this->queriedContext = $inputs['context'];
            unset($inputs['context']);
        }

        if (empty(static::PARAMETERS)) {
            if (!empty($inputs)) {
                throw new \Exception('Invalid parameters value(s)');
            }

            return;
        }

        $validator = new ParameterValidator();

        if (!$validator->validateData($inputs, static::PARAMETERS)) {
            $parameters = array_map(
                function ($i) {
                    return $i['name'];
                }, // Just display parameter names
                $validator->getInvalidParameters()
            );

            throw new \Exception(sprintf('Invalid parameters value(s): %s', implode(', ', $parameters)));
        }

        // Guess the context from input data
        if (empty($this->queriedContext)) {
            $this->queriedContext = $validator->getQueriedContext($inputs, static::PARAMETERS);
        }

        if (is_null($this->queriedContext)) {
            throw new \Exception('Required parameter(s) missing');
        } elseif ($this->queriedContext === false) {
            throw new \Exception('Mixed context parameters');
        }

        $this->setInputs($inputs, $this->queriedContext);
    }

    /**
     * Loads configuration for the bridge
     *
     * Returns errors and aborts execution if the provided configuration is
     * invalid.
     *
     * @return void
     */
    public function loadConfiguration()
    {
        foreach (static::CONFIGURATION as $optionName => $optionValue) {
            $section = $this->getShortName();
            $configurationOption = Configuration::getConfig($section, $optionName);

            if ($configurationOption !== null) {
                $this->configuration[$optionName] = $configurationOption;
                continue;
            }

            if (isset($optionValue['required']) && $optionValue['required'] === true) {
                throw new \Exception(sprintf('Missing configuration option: %s', $optionName));
            } elseif (isset($optionValue['defaultValue'])) {
                $this->configuration[$optionName] = $optionValue['defaultValue'];
            }
        }
    }

    /**
     * Returns the value for the provided input
     *
     * @param string $input The input name
     * @return mixed|null The input value or null if the input is not defined
     */
    protected function getInput($input)
    {
        if (!isset($this->inputs[$this->queriedContext][$input]['value'])) {
            return null;
        }
        return $this->inputs[$this->queriedContext][$input]['value'];
    }

    /**
     * Get the key name of a given input
     * Can process multilevel arrays with two levels, the max level a list can have
     *
     * @param string $input The input name
     * @return string|null The accompaning key to a given input or null if the input is not defined
     */
    public function getKey($input)
    {
        if (!isset($this->inputs[$this->queriedContext][$input]['value'])) {
            return null;
        }
        if (array_key_exists('global', static::PARAMETERS)) {
            if (array_key_exists($input, static::PARAMETERS['global'])) {
                $context = 'global';
            }
        }
        if (!isset($context)) {
            $context = $this->queriedContext;
        }

        $needle = $this->inputs[$this->queriedContext][$input]['value'];
        foreach (static::PARAMETERS[$context][$input]['values'] as $first_level_key => $first_level_value) {
            if (!is_array($first_level_value) && $needle === (string)$first_level_value) {
                return $first_level_key;
            } elseif (is_array($first_level_value)) {
                foreach ($first_level_value as $second_level_key => $second_level_value) {
                    if ($needle === (string)$second_level_value) {
                        return $second_level_key;
                    }
                }
            }
        }
    }

    /**
     * Get bridge configuration value
     */
    public function getOption($name)
    {
        return $this->configuration[$name] ?? null;
    }

    /** {@inheritdoc} */
    public function getDescription()
    {
        return static::DESCRIPTION;
    }

    /** {@inheritdoc} */
    public function getMaintainer()
    {
        return static::MAINTAINER;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return static::NAME;
    }

    /** {@inheritdoc} */
    public function getIcon()
    {
        return static::URI . '/favicon.ico';
    }

    /** {@inheritdoc} */
    public function getParameters()
    {
        return static::PARAMETERS;
    }

    /** {@inheritdoc} */
    public function getURI()
    {
        return static::URI;
    }

    /** {@inheritdoc} */
    public function getDonationURI()
    {
        return static::DONATION_URI;
    }

    /** {@inheritdoc} */
    public function getCacheTimeout()
    {
        return static::CACHE_TIMEOUT;
    }

    /** {@inheritdoc} */
    public function detectParameters($url)
    {
        $regex = '/^(https?:\/\/)?(www\.)?(.+?)(\/)?$/';
        if (
            empty(static::PARAMETERS)
            && preg_match($regex, $url, $urlMatches) > 0
            && preg_match($regex, static::URI, $bridgeUriMatches) > 0
            && $urlMatches[3] === $bridgeUriMatches[3]
        ) {
            return [];
        }
        return null;
    }

    /**
     * Loads a cached value for the specified key
     *
     * @return mixed Cached value or null if the key doesn't exist or has expired
     */
    protected function loadCacheValue(string $key)
    {
        $cache = RssBridge::getCache();
        $cacheKey = $this->getShortName() . '_' . $key;
        return $cache->get($cacheKey);
    }

    /**
     * Stores a value to cache with the specified key
     *
     * @param mixed $value Value to cache
     */
    protected function saveCacheValue(string $key, $value, $ttl = 86400)
    {
        $cache = RssBridge::getCache();
        $cacheKey = $this->getShortName() . '_' . $key;
        $cache->set($cacheKey, $value, $ttl);
    }

    public function getShortName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
