<?php

declare(strict_types=1);

class NullCache implements CacheInterface
{
    public function setScope($scope)
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

    public function getTime()
    {
    }

    public function purgeCache($seconds)
    {
    }
}
