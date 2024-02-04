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

    public function getFeed(): array
    {
        return [
            'name'          => $this->getName(),
            'uri'           => $this->getURI(),
            'donationUri'   => $this->getDonationURI(),
            'icon'          => $this->getIcon(),
        ];
    }

    public function getName()
    {
        return static::NAME;
    }

    public function getURI()
    {
        return static::URI ?? 'https://github.com/RSS-Bridge/rss-bridge/';
    }

    public function getDonationURI(): string
    {
        return static::DONATION_URI;
    }

    public function getIcon()
    {
        if (static::URI) {
            // This favicon may or may not exist
            return rtrim(static::URI, '/') . '/favicon.ico';
        }
        return '';
    }

    public function getOption(string $name)
    {
        return $this->configuration[$name] ?? null;
    }

    /**
     * The description is only used in bridge card rendering on frontpage
     */
    public function getDescription()
    {
        return static::DESCRIPTION;
    }

    public function getMaintainer(): string
    {
        return static::MAINTAINER;
    }

    /**
     * A more correct method name would have been "getContexts"
     */
    public function getParameters(): array
    {
        return static::PARAMETERS;
    }

    public function getItems()
    {
        return $this->items;
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
        // This is the submitted context
        $contextName = $input['context'] ?? null;
        if ($contextName) {
            // Context hinting (optional)
            $this->queriedContext = $contextName;
            unset($input['context']);
        }

        $contexts = $this->getParameters();

        if (!$contexts) {
            if ($input) {
                throw new \Exception('Invalid parameters value(s)');
            }
            return;
        }

        $validator = new ParameterValidator();

        // $input IS PASSED BY REFERENCE!
        $errors = $validator->validateInput($input, $contexts);
        if ($errors !== []) {
            $invalidParameterKeys = array_column($errors, 'name');
            throw new \Exception(sprintf('Invalid parameters value(s): %s', implode(', ', $invalidParameterKeys)));
        }

        // Guess the context from input data
        if (empty($this->queriedContext)) {
            $queriedContext = $validator->getQueriedContext($input, $contexts);
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
            foreach ($this->getParameters() as $context => $set) {
                if (array_key_exists($name, $this->getParameters()[$context])) {
                    $this->inputs[$context][$name]['value'] = $value;
                }
            }
        }

        // Apply default values to missing data
        $contextNames = [$queriedContext];
        if (array_key_exists('global', $this->getParameters())) {
            $contextNames[] = 'global';
        }

        foreach ($contextNames as $context) {
            if (!isset($this->getParameters()[$context])) {
                // unknown context provided by client, throw exception here? or continue?
            }

            foreach ($this->getParameters()[$context] as $name => $properties) {
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
        if (array_key_exists('global', $this->getParameters())) {
            foreach ($this->getParameters()['global'] as $name => $properties) {
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
            $this->inputs = [
                $queriedContext => $this->inputs[$queriedContext],
            ];
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

        $contexts = $this->getParameters();

        if (array_key_exists('global', $contexts)) {
            if (array_key_exists($input, $contexts['global'])) {
                $contextName = 'global';
            }
        }
        if (!isset($contextName)) {
            $contextName = $this->queriedContext;
        }

        $needle = $this->inputs[$this->queriedContext][$input]['value'];
        foreach ($contexts[$contextName][$input]['values'] as $first_level_key => $first_level_value) {
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

        $contexts = $this->getParameters();

        if (
            empty($contexts)
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
