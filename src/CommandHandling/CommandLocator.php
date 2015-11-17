<?php

namespace Simgroep\EventSourcing\CommandHandling;

use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CommandLocator
{
    /**
     * @param $path
     * @param $namespace
     *
     * @return CommandContainer
     */
    public function locate($path, $namespace)
    {
        $container = new CommandContainer();
        foreach ($this->findClasses($path, $namespace) as $class) {
            $container->register($class);
        }

        return $container;
    }

    /**
     * @param string $path
     * @param string $namespace
     */
    private function findClasses($path, $namespace)
    {
        $namespace = rtrim($namespace, '\\');

        /** @var SplFileInfo $file */
        $finder = new Finder();
        $finder->files()->name('*.php')->in($path);
        foreach ($finder as $file) {
            $currentNamespace = $namespace;
            if ($relativePath = $file->getRelativePath()) {
                $currentNamespace .= '\\'.strtr($relativePath, '/', '\\');
            }
            $class = $currentNamespace.'\\'.$file->getBasename('.php');
            $reflection = new ReflectionClass($class);
            if ( ! $reflection->isAbstract()) {
                yield $class;
            }
        }
    }
}

