<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection;

class ContainerBuilder implements ContainerBuilderInterface
{
    protected array $parameters = [];
    protected array $packages = [];
    protected array $services = [];
    protected array $aliases = [];

    public function build(ContainerConfiguratorInterface $containerConfigurator): ContainerInterface
    {
        $container = new Container();

        $this->addPackages($containerConfigurator->getPackages());
        $this->addParameters($containerConfigurator->getParameters());
        $this->addServices($containerConfigurator->getServices());

        foreach ($this->parameters as $name => $value) {
            $container->setParameter($name, $value);
        }

        foreach ($this->packages as $name => $value) {
            $container->setParameter('package.' . $name, $value);
        }

        $container->compile();

        foreach ($this->services as $serviceConfig) {
            $this->make($container, $serviceConfig);
        }

        return $container;
    }

    protected function make(Container $container, array $serviceConfig): void
    {
        if (is_array($serviceConfig)) {
            $object = $serviceConfig['class'] ?? '';
            $calls = $serviceConfig['calls'] ?? [];
            $arguments = $serviceConfig['arguments'] ?? [];

            $ref = new \ReflectionClass($object);
            $parameters = $ref->getConstructor()->getParameters();
            $args = [];

            foreach ($parameters as $parameter) {
                if (isset($arguments[$parameter->getName()])) {
                    $args[] = $arguments[$parameter->getName()];
                    break;
                }

                $argType = $parameter->getType()->getName();
                $serviceId = $this->aliases[$argType] ?? null;

                if (false === $container->has($serviceId)) {
                    $this->make($container, $this->services[$serviceId]);
                }

                $args[] = $container->get($serviceId);
            }

            $releaseObject =  new $object(...$args);
            $container->set($object, $releaseObject);

            foreach ($calls as $method => $call) {
                $callsArg = $container->get($call);
                $releaseObject->{$method}($callsArg);
            }
        } else {
            $object = $serviceConfig;
            $ref = new \ReflectionClass($object);
            $parameters = $ref->getConstructor()->getParameters();
            $args = [];

            foreach ($parameters as $parameter) {
                $argType = $parameter->getType()->getName();
                $serviceId = $this->aliases[$argType];

                if (false === $container->has($serviceId)) {
                    $this->make($container, $this->services[$serviceId]);
                }

                $args[] = $container->get($serviceId);
            }

            $container->set($object, (new $object(...$args)));
        }
    }

    public function register(array $serviceConfig): void
    {
        if (isset($serviceConfig[1]['resource'])) {
            $resource = $serviceConfig[1]['resource'];
            $services = $this->getFiles($resource, $serviceConfig[0]);
            $exclude = $serviceConfig[1]['exclude'] ?? null;

            if ($exclude !== $exclude) {
                $exclude = $this->getFiles($serviceConfig[1]['exclude'], $serviceConfig[0]);
                $services = array_filter($services, fn (string $item) => false === in_array($item, $exclude, true));
            }

            foreach ($services as $service) {
                $this->aliases[$service] = $service;
                $this->services[$service] = [$service, $service];
            }

            return;
        }

        if (is_string($serviceConfig[0])) {
            $aliases = [$serviceConfig[0]];
        } else {
            $aliases = $serviceConfig[0];
        }

        if (is_array($serviceConfig[1])) {
            $object = $serviceConfig[1]['class'] ?? $aliases[0];
            $serviceConfig[1]['class'] = $object;
        } else {
            $object = $serviceConfig[1];
        }

        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $object;
        }

        $this->services[$object] = $serviceConfig;
    }

    public function getFiles(string $path, string $namespace): array
    {
        $list = [];
        $recursiveDir = new \DirectoryIterator($path);

        foreach ($recursiveDir as $item) {
            if($item->isDir()) {
                if (false === in_array($item->getBasename(), ['.', '..'], true)) {
                    $list = $list + $this->getFiles($path . '/' . $item->getBasename(), $namespace . '\\' . $item->getBasename());
                }
            } else {
                if ($item->getExtension() === 'php') {
                    $className = $item->getBasename('.php');
                    $class = $namespace . '\\' . $className;
                    $list[] = $class;
                }
            }
        }

        return $list;
    }

    protected function getServiceArgs(string $object): array
    {
        $argClass = '';

        return [];
    }

    public function addPackages(array $packages): void
    {
        $this->packages = $packages + $this->packages;
    }

    public function addParameters(array $parameters): void
    {
        $this->parameters = $parameters + $this->parameters;
    }

    public function addServices(array $services): void
    {
        foreach ($services as $service) {
            $this->register($service);
        }
//        $this->services = $services + $this->services;
    }
}
