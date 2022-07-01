<?php

final class BridgeFactory
{
    private $folder;
    private $bridgeNames = [];
    private $whitelist = [];

    public function __construct(string $folder = PATH_LIB_BRIDGES)
    {
        $this->folder = $folder;

        // create names
        foreach (scandir($this->folder) as $file) {
            if (preg_match('/^([^.]+)Bridge\.php$/U', $file, $m)) {
                $this->bridgeNames[] = $m[1];
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
        if ($contents === '*') { // Whitelist all bridges
            $this->whitelist = $this->getBridgeNames();
        } else {
            foreach (explode("\n", $contents) as $bridgeName) {
                $this->whitelist[] = $this->sanitizeBridgeName($bridgeName);
            }
        }
    }

    public function create(string $name): BridgeInterface
    {
        if (preg_match('/^[A-Z][a-zA-Z0-9-]*$/', $name)) {
            $className = sprintf('%sBridge', $this->sanitizeBridgeName($name));
            return new $className();
        }
        throw new \InvalidArgumentException('Bridge name invalid!');
    }

    public function getBridgeNames(): array
    {
        return $this->bridgeNames;
    }

    public function isWhitelisted($name): bool
    {
        return in_array($this->sanitizeBridgeName($name), $this->whitelist);
    }

    private function sanitizeBridgeName($name)
    {

        if (!is_string($name)) {
            return null;
        }

        // Trim trailing '.php' if exists
        if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
            $name = $matches[1];
        }

        // Trim trailing 'Bridge' if exists
        if (preg_match('/(.+)(?:Bridge)/i', $name, $matches)) {
            $name = $matches[1];
        }

        // Improve performance for correctly written bridge names
        if (in_array($name, $this->getBridgeNames())) {
            $index = array_search($name, $this->getBridgeNames());
            return $this->getBridgeNames()[$index];
        }

        // The name is valid if a corresponding bridge file is found on disk
        if (in_array(strtolower($name), array_map('strtolower', $this->getBridgeNames()))) {
            $index = array_search(strtolower($name), array_map('strtolower', $this->getBridgeNames()));
            return $this->getBridgeNames()[$index];
        }

        Debug::log('Invalid bridge name specified: "' . $name . '"!');
        return null;
    }
}
