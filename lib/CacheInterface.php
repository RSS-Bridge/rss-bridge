<?php

interface CacheInterface
{
    public function setScope(string $scope): void;

    public function setKey(array $key): void;

    public function loadData();

    public function saveData($data): void;

    public function getTime(): ?int;

    public function purgeCache(int $seconds): void;
}
