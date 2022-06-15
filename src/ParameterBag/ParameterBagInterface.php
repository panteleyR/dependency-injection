<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection\ParameterBag;

interface ParameterBagInterface
{
    public function clear(): void;
    public function add(array $parameters): void;
    public function all(): array;
    public function get(string $name): array|bool|string|int|float|\UnitEnum|null;
    public function remove(string $name): void;
    public function set(string $name, array|bool|string|int|float|\UnitEnum|null $value): void;
    public function has(string $name): bool;
    public function resolve(): void;
}
