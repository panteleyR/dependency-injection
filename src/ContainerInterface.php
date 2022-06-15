<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function set(string $id, null|object $object): void;
}
