<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class ContainerConfigurator implements ContainerConfiguratorInterface
{
    protected array $parameters = [];
    protected array $packages = [];
    protected array $services = [];
    protected array $serviceProviders = [];

    public function import(string $path, string $type = null): void
    {
        $configFilePathList = glob($path, GLOB_NOSORT);
        $parser = $this->getParser();

        foreach ($configFilePathList as $configFilePath) {
            $configTree = $parser->parseFile($configFilePath, Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS);
            $this->parameters = $configTree['parameters'] ?? [] + $this->parameters;

            foreach ($configTree['services'] ?? [] as $serviceId => $serviceConfig) {
                $this->services[] = [$serviceId, $serviceConfig];
            }

            foreach ($configTree['service_providers'] ?? [] as $serviceProvider) {
                $this->serviceProviders[] = $serviceProvider;
            }
        }
    }

    protected function getParser(): Parser
    {
        return new Parser();
    }

    public function importPackage(string $path, string $type = null): void
    {
        $configFilePathList = glob($path, GLOB_NOSORT);
        $parser = $this->getParser();

        foreach ($configFilePathList as $configFilePath) {
            $package = $parser->parseFile($path, Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS);
            $this->packages[basename($configFilePath, '.yaml')] = $package;
        }
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getPackages(): array
    {
        return $this->packages;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getServiceProviders(): array
    {
        return $this->serviceProviders;
    }
}
