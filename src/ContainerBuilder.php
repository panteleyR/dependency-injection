<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection;

class ContainerBuilder implements ContainerBuilderInterface
{
    protected array $parameters = [];
    protected array $packages = [];
    protected array $services = [];
    protected array $serviceProviders = [];
    protected array $aliases = [];

    public function build(ContainerInterface $container): void
    {
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

        $container->setAliases($this->aliases);
        $container->setAliases(['container' => $container::class, ContainerInterface::class => $container::class]);
        $container->set($container::class, $container);

        foreach ($this->serviceProviders as $serviceProvider) {
            $this->make($container, $serviceProvider);
        }
    }

    protected function make(Container $container, Definition $serviceConfig): void
    {
        $serviceClass = $serviceConfig->getClass();
        $ref = new \ReflectionClass($serviceClass);
        $parameters = $ref->getConstructor()?->getParameters() ?? [];
        $args = [];
        foreach ($parameters as $parameter) {
            if (isset($serviceConfig->getArguments()[$parameter->getName()])) {
                $args[] = $serviceConfig->getArguments()[$parameter->getName()];
                break;
            }

            $argType = $parameter->getType()->getName();
            $serviceId = $this->aliases[$argType];

            if (false === $container->has($serviceId)) {
                $this->make($container, $this->services[$serviceId]);
            }

            $args[] = $container->get($serviceId);
        }

        $serviceObject = new $serviceClass(...$args);
        $container->set($serviceClass, $serviceObject);

        foreach ($serviceConfig->getCalls() as $call) {
            if (is_array($call)) {
                $method = key($call);
                $class = current($call);
            } else {
                $method = $call;
                $callsArgs = $ref->getMethod($method)?->getParameters();
                foreach ($callsArgs as $callsArg) {
                    if (false === $container->has($class)) {
                        $this->make($container, $this->services[$class]);
                    }
                    $class = $container->get($callsArg->getType()->getName());
                }
            }

            $serviceObject->{$method}($class);
        }
    }
//
//    protected function make(Container $container, string|array $serviceConfig): void
//    {
//        if (is_array($serviceConfig)) {
//            $object = $serviceConfig['class'] ?? '';
//            $calls = $serviceConfig['calls'] ?? [];
//            $arguments = $serviceConfig['arguments'] ?? [];
//
//            $ref = new \ReflectionClass($object);
//            $parameters = $ref->getConstructor()->getParameters();
//            $args = [];
//
//            foreach ($parameters as $parameter) {
//                if (isset($arguments[$parameter->getName()])) {
//                    $args[] = $arguments[$parameter->getName()];
//                    break;
//                }
//
//                $argType = $parameter->getType()->getName();
//                $serviceId = $this->aliases[$argType] ?? null;
//
//                if (false === $container->has($serviceId)) {
//                    $this->make($container, $this->services[$serviceId]);
//                }
//
//                $args[] = $container->get($serviceId);
//            }
//
//            $releaseObject =  new $object(...$args);
//            $container->set($object, $releaseObject);
//
//            foreach ($calls as $method => $call) {
//                $callsArg = $container->get($call);
//                $releaseObject->{$method}($callsArg);
//            }
//        } else {
//            $object = $serviceConfig;
//            $ref = new \ReflectionClass($object);
//            $parameters = $ref->getConstructor()?->getParameters() ?? [];
//            $args = [];
//
//            foreach ($parameters as $parameter) {
//                $argType = $parameter->getType()->getName();
//                $serviceId = $this->aliases[$argType];
//
//                if (false === $container->has($serviceId)) {
//                    $this->make($container, $this->services[$serviceId]);
//                }
//
//                $args[] = $container->get($serviceId);
//            }
//
//            $container->set($object, (new $object(...$args)));
//        }
//    }

    public function register(array $serviceConfig): void
    {
        if (isset($serviceConfig[1]['resource'])) {
            $resource = $serviceConfig[1]['resource'];
            $exclude = $serviceConfig[1]['exclude'] ?? [];

            if ([] !== $exclude) {
                $exclude = glob(getcwd() . $exclude, GLOB_BRACE);
            }

            $services = $this->getFiles($resource, $serviceConfig[0], $exclude);

            foreach ($services as $service) {
                $this->services[$service] = new Definition($service);
            }

            return;
        }

        if (is_array($serviceConfig[1])) {
            $definition = new Definition(
                $serviceConfig[1]['class'] ?? $serviceConfig[0],
                $serviceConfig[1]['arguments'] ?? [],
                $serviceConfig[1]['calls'] ?? [],
            );
        } else {
            $definition = new Definition($serviceConfig[1]);
        }

        $this->services[$definition->getClass()] = $definition;

        if (is_string($serviceConfig[0])) {
            $aliases = [$serviceConfig[0]];
        } else {
            $aliases = $serviceConfig[0];
        }

        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $definition->getClass();
        }
    }

    public function getFiles(string $path, string $namespace, array $exclude): array
    {
        $list = [];
        $recursiveDir = new \DirectoryIterator(getcwd() . $path);

        foreach ($recursiveDir as $item) {
            if ($item->isDir()) {
                if (false === in_array($item->getBasename(), ['.', '..'], true)) {
                    $recursiveDir = $this->getFiles(
                        $path . '/' . $item->getBasename(),
                        $namespace . '\\' . $item->getBasename(),
                        $exclude,
                    );
                    array_push($list, ...$recursiveDir);
                }
            } else {
                if ($item->getExtension() === 'php' && false === in_array($item->getRealPath(), $exclude, true)) {
                    $className = $item->getBasename('.php');
                    $class = $namespace . '\\' . $className;
                    $list[] = $class;
                }
            }
        }

        return $list;
    }

    public function addPackages(array $packages): void
    {
        $this->packages = $packages + $this->packages;
    }

    public function addParameters(array $parameters): void
    {
        $this->parameters = $parameters + $this->parameters;
    }

    public function addServiceProviders(array $serviceProviders): void
    {
        foreach ($serviceProviders as $serviceProvider) {
            $this->serviceProviders[] = new Definition($serviceProvider);
        }
    }

    public function addServices(array $services): void
    {
        foreach ($services as $service) {
            $this->register($service);
        }
    }
}
