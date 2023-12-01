<?php

abstract class BridgeAbstract
{
    const NAME = 'Unnamed bridge';
    const URI = '';
    const DONATION_URI = '';
    const DESCRIPTION = 'No description provided';
    const MAINTAINER = 'No maintainer';
    const CACHE_TIMEOUT = 3600;
    const CONFIGURATION = [];
    const PARAMETERS = [];
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

    protected array $items = [];
    protected array $inputs = [];
    protected ?string $queriedContext = '';
    private array $configuration = [];

    protected CacheInterface $cache;
    protected Logger $logger;

    public function __construct(
        CacheInterface $cache,
        Logger $logger
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    abstract public function collectData();

    public function getItems()
    {
        return $this->items;
    }

    public function getOption(string $name)
    {
        return $this->configuration[$name] ?? null;
    }

    public function getDescription()
    {
        return static::DESCRIPTION;
    }

    public function getMaintainer(): string
    {
        return static::MAINTAINER;
    }

    public function getName()
    {
        return static::NAME;
    }

    public function getIcon()
    {
        return static::URI . '/favicon.ico';
    }

    public function getParameters(): array
    {
        return static::PARAMETERS;
    }

    public function getURI()
    {
        return static::URI;
    }

    public function getDonationURI(): string
    {
        return static::DONATION_URI;
    }

    public function getCacheTimeout()
    {
        return static::CACHE_TIMEOUT;
    }

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

    public function setInput(array $input)
    {
        $context = $input['context'] ?? null;
        if ($context) {
            // Context hinting (optional)
            $this->queriedContext = $context;
            unset($input['context']);
        }

        $parameters = $this->getParameters();

        if (!$parameters) {
            if ($input) {
                throw new \Exception('Invalid parameters value(s)');
            }
            return;
        }

        $validator = new ParameterValidator();

        // $input is passed by reference!
        if (!$validator->validateInput($input, $parameters)) {
            $invalidParameterKeys = array_column($validator->getInvalidParameters(), 'name');
            throw new \Exception(sprintf('Invalid parameters value(s): %s', implode(', ', $invalidParameterKeys)));
        }

        // Guess the context from input data
        if (empty($this->queriedContext)) {
            $queriedContext = $validator->getQueriedContext($input, $parameters);
            $this->queriedContext = $queriedContext;
        }

        if (is_null($this->queriedContext)) {
            throw new \Exception('Required parameter(s) missing');
        } elseif ($this->queriedContext === false) {
            throw new \Exception('Mixed context parameters');
        }

        $this->setInputWithContext($input, $this->queriedContext);
    }

    private function setInputWithContext(array $input, $queriedContext)
    {
        // Import and assign all inputs to their context
        foreach ($input as $name => $value) {
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
                        $this->inputs[$context][$name]['value'] = $input[$context][$name]['value'] ?? false;
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
                if (isset($input[$name])) {
                    $value = $input[$name];
                } else {
                    if ($properties['type'] ?? null === 'checkbox') {
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

    protected function getInput($input)
    {
        return $this->inputs[$this->queriedContext][$input]['value'] ?? null;
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

    protected function loadCacheValue(string $key)
    {
        $cacheKey = $this->getShortName() . '_' . $key;
        return $this->cache->get($cacheKey);
    }

    protected function saveCacheValue(string $key, $value, $ttl = 86400)
    {
        $cacheKey = $this->getShortName() . '_' . $key;
        $this->cache->set($cacheKey, $value, $ttl);
    }

    public function getShortName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
