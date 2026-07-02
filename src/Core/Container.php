<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Минимальный DI-контейнер с reflection-автовайрингом: если для класса нет
 * явного биндинга, конструктор резолвится рекурсивно по типам параметров.
 */
final class Container
{
    /** @var array<class-string, \Closure> */
    private array $bindings = [];

    /** @var array<class-string, object> */
    private array $instances = [];

    public function singleton(string $abstract, \Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function instance(string $abstract, object $object): void
    {
        $this->instances[$abstract] = $object;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function make(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        if (isset($this->bindings[$class])) {
            $object = ($this->bindings[$class])($this);
            $this->instances[$class] = $object;

            return $object;
        }

        if (!class_exists($class)) {
            throw new \RuntimeException("Class {$class} does not exist");
        }

        $reflection = new \ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Class {$class} is not instantiable");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            $object = new $class();
            $this->instances[$class] = $object;

            return $object;
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string $dependency */
                $dependency = $type->getName();
                $arguments[] = $this->make($dependency);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \RuntimeException(
                "Cannot resolve parameter \${$parameter->getName()} for {$class}"
            );
        }

        $object = $reflection->newInstanceArgs($arguments);
        $this->instances[$class] = $object;

        return $object;
    }
}
