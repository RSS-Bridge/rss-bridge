<?php

final class BridgeFactory
{
    private $folder;
    /** @var array<class-string<BridgeInterface>> */
    private $bridgeClassNames = [];
    /** @var array<class-string<BridgeInterface>> */
    private $whitelist = [];

    public function __construct(string $folder = PATH_LIB_BRIDGES)
    {
        $this->folder = $folder;

        // create names
        foreach (scandir($this->folder) as $file) {
            if (preg_match('/^([^.]+Bridge)\.php$/U', $file, $m)) {
                $this->bridgeClassNames[] = $m[1];
            }
        }

        // create whitelist
        if (file_exists(WHITELIST)) {
            $contents = trim(file_get_contents(WHITELIST));
        } elseif (file_exists(WHITELIST_DEFAULT)) {
            $contents = trim(file_get_contents(WHITELIST_DEFAULT));
        } else {
            $contents = '';
        }
        if ($contents === '*') {
            // Whitelist all bridges
            $this->whitelist = $this->getBridgeClassNames();
        } else {
            foreach (explode("\n", $contents) as $bridgeName) {
                $bridgeClassName = $this->sanitizeBridgeName($bridgeName);
                if ($bridgeClassName !== null) {
                    $this->whitelist[] = $bridgeClassName;
                }
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

    /**
     * @return array<class-string<BridgeInterface>>
     */
    public function getBridgeClassNames(): array
    {
        return $this->bridgeClassNames;
    }

    /**
     * @param class-string<BridgeInterface>|null $name
     */
    public function isWhitelisted(string $name): bool
    {
        return in_array($name, $this->whitelist);
    }

    /**
     * Tries to turn a potentially human produced bridge name into a class name.
     *
     * @param mixed $name
     * @return class-string<BridgeInterface>|null
     */
    public function sanitizeBridgeName($name): ?string
    {
        if (!is_string($name)) {
            return null;
        }

        // Trim trailing '.php' if exists
        if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
            $name = $matches[1];
        }

        // Append 'Bridge' suffix if not present.
        if (!preg_match('/(Bridge)$/i', $name)) {
            $name = sprintf('%sBridge', $name);
        }

        // Improve performance for correctly written bridge names
        if (in_array($name, $this->getBridgeClassNames())) {
            $index = array_search($name, $this->getBridgeClassNames());
            return $this->getBridgeClassNames()[$index];
        }

        // The name is valid if a corresponding bridge file is found on disk
        if (in_array(strtolower($name), array_map('strtolower', $this->getBridgeClassNames()))) {
            $index = array_search(strtolower($name), array_map('strtolower', $this->getBridgeClassNames()));
            return $this->getBridgeClassNames()[$index];
        }

        return null;
    }
}
