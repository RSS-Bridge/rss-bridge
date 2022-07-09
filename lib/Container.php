<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

/**
 * Simple dependency injection container holding an instance for each requested class.
 */
final class Container
{
    /** @var array<string, \Closure> */
    private array $configuration = [];

    /** @var array<string, mixed> */
    private array $registry = [];

    private static ?self $instance = null;

    /**
     * Temporary singleton method while we migrate to full DI.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registers a custom rule for instantiating certain class or interface.
     */
    public function add(string $id, \Closure $factory): void
    {
        $configuration[$id] = $factory;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws Exception  No entry was found for **this** identifier.
     * @throws Exception Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            $configurationEntry = $this->configuration[$id] ?? null;
            if ($configurationEntry === null) {
                $registry[$id] = $this->tryCreateInstance($id);
            } else {
                $registry[$id] = $configurationEntry();
            }
        }

        return $registry[$id];
    }

    /**
     * Tries to create an instance for given class.
     *
     * @param class-string $id Name of the class to create.
     *
     * @throws Exception  No entry was found for **this** identifier.
     * @throws Exception Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    private function tryCreateInstance(string $id)
    {
        if (! class_exists($id)) {
            $msg = "Class “{$id}” does not exist.";
            throw new \Exception($msg);
        }

        $class = new \ReflectionClass($id);
        if (! $class->isInstantiable()) {
            $msg = "Class “{$id}” is not instantiable.";
            throw new \Exception($msg);
        }

        $constructor = $class->getConstructor();
        if ($constructor !== null) {
            $args = $this->tryPrepareArguments($constructor);
            return new $id(...$args);
        }

        return new $id();
    }

    private function tryPrepareArguments(\ReflectionFunctionAbstract $function): array
    {
        $args = [];
        foreach ($function->getParameters() as $key => $param) {
            $type = $param->getType();
            if ($type === null) {
                $msg = "The argument {$key} of “{$function}” lacks a typehint.";
                throw new \Exception($msg);
            }

            $className = $type instanceof \ReflectionNamedType && !$type->isBuiltIn() ? $type->getName() : null;
            if ($className === null) {
                $msg = "Do not know how to instantiate the type of argument {$key} of “{$function}”.";
                throw new \Exception($msg);
            }

            $args[$key] = $this->get($className);
        }

        return $args;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     */
    public function has(string $id): bool
    {
        return isset($registry[$id]);
    }
}
