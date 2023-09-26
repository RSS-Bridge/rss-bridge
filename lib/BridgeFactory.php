<?php

final class BridgeFactory
{
    private CacheInterface $cache;
    private Logger $logger;
    private array $bridgeClassNames = [];
    private array $enabledBridges = [];
    private array $missingEnabledBridges = [];

    public function __construct()
    {
        $this->cache = RssBridge::getCache();
        $this->logger = RssBridge::getLogger();

        // Create all possible bridge class names from fs
        foreach (scandir(__DIR__ . '/../bridges/') as $file) {
            if (preg_match('/^([^.]+Bridge)\.php$/U', $file, $m)) {
                $this->bridgeClassNames[] = $m[1];
            }
        }

        $enabledBridges = Configuration::getConfig('system', 'enabled_bridges');
        if ($enabledBridges === null) {
            throw new \Exception('No bridges are enabled...');
        }
        foreach ($enabledBridges as $enabledBridge) {
            if ($enabledBridge === '*') {
                $this->enabledBridges = $this->bridgeClassNames;
                break;
            }
            $bridgeClassName = $this->createBridgeClassName($enabledBridge);
            if ($bridgeClassName) {
                $this->enabledBridges[] = $bridgeClassName;
            } else {
                $this->missingEnabledBridges[] = $enabledBridge;
                $this->logger->info(sprintf('Bridge not found: %s', $enabledBridge));
            }
        }
    }

    public function create(string $name): BridgeAbstract
    {
        return new $name($this->cache, $this->logger);
    }

    public function isEnabled(string $bridgeName): bool
    {
        return in_array($bridgeName, $this->enabledBridges);
    }

    public function createBridgeClassName(string $bridgeName): ?string
    {
        $name = self::normalizeBridgeName($bridgeName);
        $namesLoweredCase = array_map('strtolower', $this->bridgeClassNames);
        $nameLoweredCase = strtolower($name);
        if (! in_array($nameLoweredCase, $namesLoweredCase)) {
            return null;
        }
        $index = array_search($nameLoweredCase, $namesLoweredCase);
        return $this->bridgeClassNames[$index];
    }

    public static function normalizeBridgeName(string $name)
    {
        if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
            $name = $matches[1];
        }
        if (!preg_match('/(Bridge)$/i', $name)) {
            $name = sprintf('%sBridge', $name);
        }
        return $name;
    }

    public function getBridgeClassNames(): array
    {
        return $this->bridgeClassNames;
    }

    public function getMissingEnabledBridges(): array
    {
        return $this->missingEnabledBridges;
    }
}
