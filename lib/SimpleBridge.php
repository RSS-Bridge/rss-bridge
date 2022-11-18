<?php

declare(strict_types=1);

/**
 * The purpose of this class is to make it very easy to create a new bridge.
 * The new bridge needs only to implement the __invoke() method.
 */
abstract class SimpleBridge extends BridgeAbstract
{
    private ?string $name = null;
    private ?string $uri = null;

    public function collectData()
    {
        $data = $this();

        // This is confusing but allows for five different ways to return items
        if ($data === []) {
            $this->items = [];
        } elseif (isset($data['items'])) {
            if (isset($data['items'][0])) {
                $this->items = $data['items'];
            } else {
                $this->items[] = $data['items'];
            }
            $this->uri = $data['uri'] ?? null;
            $this->name = $data['name'] ?? null;
        } elseif (isset($data[0]) && is_array($data[0])) {
            $this->items = $data;
        } else {
            $this->items[] = $data;
        }
    }

    public function getURI()
    {
        return $this->uri ?? parent::getURI();
    }

    public function getName()
    {
        return $this->name ?? parent::getName();
    }

    abstract public function __invoke(): array;
}
