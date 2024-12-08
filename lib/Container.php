<?php

declare(strict_types=1);

class Container implements \ArrayAccess
{
    private array $values = [];
    private array $resolved = [];

    public function offsetSet($offset, $value): void
    {
        $this->values[$offset] = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!isset($this->values[$offset])) {
            throw new \Exception(sprintf('Unknown container key: "%s"', $offset));
        }
        if (!isset($this->resolved[$offset])) {
            $this->resolved[$offset] = $this->values[$offset]($this);
        }
        return $this->resolved[$offset];
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
    }

    public function offsetUnset($offset): void
    {
    }
}
