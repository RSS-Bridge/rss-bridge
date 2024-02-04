<?php

class FormatFactory
{
    private array $formatNames = [];

    public function __construct()
    {
        $iterator = new \FilesystemIterator(__DIR__ . '/../formats');
        foreach ($iterator as $file) {
            if (preg_match('/^([^.]+)Format\.php$/U', $file->getFilename(), $m)) {
                $this->formatNames[] = $m[1];
            }
        }
        sort($this->formatNames);
    }

    public function create(string $name): FormatAbstract
    {
        if (! preg_match('/^[a-zA-Z0-9-]*$/', $name)) {
            throw new \InvalidArgumentException('Format name invalid!');
        }
        $sanitizedName = $this->sanitizeName($name);
        if (!$sanitizedName) {
            throw new \InvalidArgumentException(sprintf('Unknown format given `%s`', $name));
        }
        $className = '\\' . $sanitizedName . 'Format';
        return new $className();
    }

    public function getFormatNames(): array
    {
        return $this->formatNames;
    }

    protected function sanitizeName(string $name): ?string
    {
        $name = ucfirst(strtolower($name));
        // Trim trailing '.php' if exists
        if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
            $name = $matches[1];
        }
        // Trim trailing 'Format' if exists
        if (preg_match('/(.+)(?:Format)/i', $name, $matches)) {
            $name = $matches[1];
        }
        if (in_array($name, $this->formatNames)) {
            return $name;
        }
        return null;
    }
}
