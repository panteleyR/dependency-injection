<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function set(string $id, null|object $service): void;
    public function getParameter(string $name): array|bool|string|int|float|\UnitEnum|null;
    public function setParameter(string $name, array|bool|string|int|float|\UnitEnum|null $value): void;
    public function setAliases(array $aliases): void;
}
