<?php

declare(strict_types=1);

namespace Lilith\DependencyInjection;

use Lilith\DependencyInjection\YamlParser\Parser;
use Lilith\DependencyInjection\YamlParser\Yaml;

class ContainerConfigurator implements ContainerConfiguratorInterface
{
    protected array $parameters = [];
    protected array $packages = [];
    protected array $services = [];

    public function import(string $path, string $type = null): void
    {
        $configFilePathList = glob($path, GLOB_NOSORT);
        $parser = $this->getParser();

        foreach ($configFilePathList as $configFilePath) {
            $configTree = $parser->parseFile($configFilePath, Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS);

//            if (isset($configTree['imports'])) {
//                foreach ($configTree['imports'] as $importingPath){
//                    $this->import($importingPath);
//                }
//            }

            $this->parameters = $configTree['parameters'] + $this->parameters;
            foreach ($configTree['services'] as $serviceId => $serviceConfig) {
                $this->services[] = [$serviceId, $serviceConfig];
            }
            $this->services = $configTree['services'] + $this->services;
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

    protected function parseFile(string $path, Parser $parser): array
    {
        $configTree = $parser->parseFile($path, Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS);
//@TODO Сделать импорт
//        if (isset($configTree['import'])) {
//            foreach ($configTree['import'] as $importingPackagePath){
//                $importedConfigTree = $this->parseFile($importingPackagePath, $parser);
//                $configTree = $configTree + $importedConfigTree;
//            }
//        }

        return $configTree;
    }

    public function compile(string $path): void
    {

    }

    public function compilePackage(string $path): void
    {

    }

    public function addParameters(array $parameters): void
    {
        $this->parameters = $parameters + $this->parameters;
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

    public function show(): array
    {
        return [
            'packages' => $this->getPackages(),
            'parameters' => $this->getParameters(),
            'services' => $this->getServices(),
        ];
    }
}
