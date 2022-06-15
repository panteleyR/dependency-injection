<?php

declare(strict_types=1);

namespace Lilith\Compiler;

class Compiler
{
//    public function getFiles(string $path, string $namespace)
//    {
//        $recursiveDir = new \RecursiveDirectoryIterator($path);
//
//        foreach ($recursiveDir as $item) {
////            if($item->get) {}
//            if($item->isDir()) {
//                yield $this->getFiles($path . $item->getBasename(), $namespace . $item->getBasename());
//            } else {
//                $className = $item->getBasename('.php');
//                $class = $namespace . '\\' . $className;
//                yield $class;
//            }
//        }
////        $files $path
//    }
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
}
