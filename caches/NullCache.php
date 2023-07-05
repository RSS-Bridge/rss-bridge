<?php

declare(strict_types=1);

class NullCache implements CacheInterface
{
    public function setScope(string $scope): void
    {
    }

    public function setKey($key)
    {
    }

    public function loadData()
    {
    }

    public function saveData($data)
    {
    }

    public function getTime(): ?int
    {
        return null;
    }

    public function purgeCache($seconds)
    {
    }
}
