<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection;

use Lilith\DependencyInjection\ParameterBag\ParameterBag;
use Lilith\DependencyInjection\ParameterBag\ParameterBagInterface;

class Container implements ContainerInterface
{
    protected ParameterBagInterface $parameterBag;
    protected array $services = [];
//    protected $privates = [];
//    protected $fileMap = [];
//    protected $methodMap = [];
    protected array $factories = [];
    protected array $aliases = [];
//    protected $loading = [];
    protected $resolving = [];


    public function __construct(ParameterBagInterface $parameterBag = null)
    {
        $this->parameterBag = $parameterBag ?? new ParameterBag();
    }

//    public function import(string $path): void
//    {
//
//    }

    public function getParameter(string $name): array|bool|string|int|float|\UnitEnum|null
    {
        return $this->parameterBag->get($name);
    }

    public function hasParameter(string $name): bool
    {
        return $this->parameterBag->has($name);
    }

    public function setParameter(string $name, array|bool|string|int|float|\UnitEnum|null $value)
    {
        $this->parameterBag->set($name, $value);
    }

    public function compile(): void
    {
        $this->parameterBag->resolve();
    }

    public function get(string $id): mixed
    {
        return $this->services[$id]
            ?? $this->services[$id = $this->aliases[$id] ?? $id]
            ?? ($this->factories[$id] ?? [$this, 'make'])($id);
    }

    public function set(string $id, null|object $service): void
    {
        if (isset($this->services[$id])) {
//            throw new InvalidArgumentException(sprintf('The "%s" service is already initialized, you cannot replace it.', $id));
        }

        if (isset($this->aliases[$id])) {
            unset($this->aliases[$id]);
        }

        if (null === $service) {
            unset($this->services[$id]);

            return;
        }

        $this->services[$id] = $service;
    }

    public function has(string $id): bool
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (isset($this->services[$id])) {
            return true;
        }

        return false;
    }

    private function make(string $id)
    {

    }
}
//    public function set(string $id, ?object $service);
//    public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE);
//    public function has(string $id);
//    public function initialized(string $id);
//    public function getParameter(string $name);
//    public function hasParameter(string $name);
//    public function setParameter(string $name, $value);
//}
