<?php

final class BridgeFactory
{
    private $bridgeClassNames = [];
    private $enabledBridges = [];

    public function __construct()
    {
        // Create all possible bridge class names from fs
        foreach (scandir(__DIR__ . '/../bridges/') as $file) {
            if (preg_match('/^([^.]+Bridge)\.php$/U', $file, $m)) {
                $this->bridgeClassNames[] = $m[1];
            }
        }

        // Create enabled bridges from whitelist file
        if (file_exists(WHITELIST)) {
            $whitelist = trim(file_get_contents(WHITELIST));
        } elseif (file_exists(WHITELIST_DEFAULT)) {
            $whitelist = trim(file_get_contents(WHITELIST_DEFAULT));
        } else {
            $whitelist = '';
        }

        if ($whitelist === '*') {
            // Enable all bridges
            $this->enabledBridges = $this->getBridgeClassNames();
        } else {
            $bridgeNames = explode("\n", $whitelist);
            foreach ($bridgeNames as $bridgeName) {
                $this->enabledBridges[] = $this->createBridgeClassName($bridgeName);
            }
        }
    }

    /**
     * @param class-string<BridgeInterface> $name
     */
    public function create(string $name): BridgeInterface
    {
        return new $name();
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
            throw new \Exception(sprintf('Bridge name invalid: %s', $bridgeName));
        }

        $index = array_search($nameLoweredCase, $namesLoweredCase);

        return $this->bridgeClassNames[$index];
    }

    public static function normalizeBridgeName(string $name)
    {
        // Trim trailing '.php' if exists
        if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
            $name = $matches[1];
        }

        // Append 'Bridge' suffix if not present.
        if (!preg_match('/(Bridge)$/i', $name)) {
            $name = sprintf('%sBridge', $name);
        }
        return $name;
    }

    /**
     * @return array<class-string<BridgeInterface>>
     */
    public function getBridgeClassNames(): array
    {
        return $this->bridgeClassNames;
    }
}
