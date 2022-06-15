<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection;

interface ContainerBuilderInterface
{
    public function build(ContainerConfiguratorInterface $containerConfigurator): ContainerInterface;
    public function addPackages(array $packages): void;
    public function addParameters(array $parameters): void;
    public function addServices(array $services): void;
}
