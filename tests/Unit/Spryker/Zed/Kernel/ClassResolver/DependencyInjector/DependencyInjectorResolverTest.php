<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Unit\Spryker\Zed\Kernel\ClassResolver\DependencyInjector;

use Spryker\Shared\Kernel\ContainerInterface;
use Spryker\Zed\Kernel\ClassResolver\DependencyInjector\DependencyInjectorResolver;
use Spryker\Zed\Kernel\Dependency\Injector\AbstractDependencyInjector;
use Spryker\Zed\Kernel\Dependency\Injector\DependencyInjectorCollectionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group Unit
 * @group Spryker
 * @group Zed
 * @group Kernel
 * @group ClassResolver
 * @group DependencyInjector
 * @group DependencyInjectorResolverTest
 */
class DependencyInjectorResolverTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The bundle which calls the `getProvidedDependency()`
     *
     * @var string
     */
    protected $injectFromBundle = 'Kernel';

    /**
     * The bundle which want to inject dependencies into a certain bundle
     *
     * @var string
     */
    protected $injectToBundle = 'Foo';

    /**
     * @var string
     */
    protected $coreClass = 'Unit\\Spryker\\Zed\\Kernel\\ClassResolver\\Fixtures\\FooDependencyInjector';

    /**
     * @var string
     */
    protected $projectClass = 'Unit\\Pyz\\Zed\\Kernel\\ClassResolver\\Fixtures\\FooDependencyInjector';

    /**
     * @var string
     */
    protected $storeClass = 'Unit\\Pyz\\Zed\\KernelDE\\ClassResolver\\Fixtures\\FooDependencyInjector';

    /**
     * @var string
     */
    protected $classPattern = 'Unit\\%namespace%\\Zed\\%fromBundle%%store%\\ClassResolver\\Fixtures\\%bundle%DependencyInjector';

    /**
     * @var array
     */
    protected $createdFiles = [];

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->deleteCreatedFiles();
    }

    /**
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Kernel\ClassResolver\DependencyInjector\DependencyInjectorResolver
     */
    protected function getResolverMock(array $methods)
    {
        $dependencyInjectorResolverMock = $this->getMock(DependencyInjectorResolver::class, $methods);

        return $dependencyInjectorResolverMock;
    }

    /**
     * @return void
     */
    public function testResolveShouldReturnEmptyCollection()
    {
        $resolverMock = $this->getResolverMock(['canResolve']);
        $resolverMock->method('canResolve')
            ->willReturn(false);

        $dependencyInjectorCollection = $resolverMock->resolve('CatFace');

        $this->assertInstanceOf(
            DependencyInjectorCollectionInterface::class,
            $dependencyInjectorCollection
        );

        $this->assertCount(0, $dependencyInjectorCollection);
    }

    /**
     * @return void
     */
    public function testResolveMustReturnCoreClass()
    {
        $this->createClass($this->coreClass);

        $resolverMock = $this->getResolverMock(['getClassPattern', 'getDependencyInjectorConfiguration']);
        $resolverMock->method('getClassPattern')
            ->willReturn($this->classPattern);

        $resolverMock->method('getDependencyInjectorConfiguration')
            ->willReturn([$this->injectToBundle => [$this->injectFromBundle]]);

        $dependencyInjectorCollection = $resolverMock->resolve($this->injectToBundle);

        $this->assertInstanceOf(
            DependencyInjectorCollectionInterface::class,
            $dependencyInjectorCollection
        );

        $resolvedDependencyInjector = current($dependencyInjectorCollection->getDependencyInjector());
        $this->assertInstanceOf($this->coreClass, $resolvedDependencyInjector);
    }

    /**
     * @return void
     */
    public function testResolveMustReturnProjectClass()
    {
        $this->createClass($this->coreClass);
        $this->createClass($this->projectClass);

        $resolverMock = $this->getResolverMock(['getClassPattern', 'getDependencyInjectorConfiguration']);
        $resolverMock->method('getClassPattern')
            ->willReturn($this->classPattern);

        $resolverMock->method('getDependencyInjectorConfiguration')
            ->willReturn([$this->injectToBundle => [$this->injectFromBundle]]);

        $dependencyInjectorCollection = $resolverMock->resolve($this->injectToBundle);

        $this->assertInstanceOf(
            DependencyInjectorCollectionInterface::class,
            $dependencyInjectorCollection
        );

        $resolvedDependencyInjector = current($dependencyInjectorCollection->getDependencyInjector());
        $this->assertInstanceOf($this->projectClass, $resolvedDependencyInjector);
    }

    /**
     * @return void
     */
    public function testResolveMustReturnStoreClass()
    {
        $this->createClass($this->projectClass);
        $this->createClass($this->storeClass);

        $resolverMock = $this->getResolverMock(['getClassPattern', 'getDependencyInjectorConfiguration']);
        $resolverMock->method('getClassPattern')
            ->willReturn($this->classPattern);

        $resolverMock->method('getDependencyInjectorConfiguration')
            ->willReturn([$this->injectToBundle => [$this->injectFromBundle]]);

        $dependencyInjectorCollection = $resolverMock->resolve($this->injectToBundle);

        $this->assertInstanceOf(
            DependencyInjectorCollectionInterface::class,
            $dependencyInjectorCollection
        );

        $resolvedDependencyInjector = current($dependencyInjectorCollection->getDependencyInjector());
        $this->assertInstanceOf($this->storeClass, $resolvedDependencyInjector);
    }

    /**
     * @return void
     */
    public function testGetClassPattern()
    {
        $dependencyInjectorResolver = new DependencyInjectorResolver();
        $this->assertSame('\%namespace%\Zed\%fromBundle%%store%\Dependency\Injector\%bundle%DependencyInjector', $dependencyInjectorResolver->getClassPattern());
    }

    /**
     * @return void
     */
    private function deleteCreatedFiles()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->createdFiles);
    }

    /**
     * @param string $className
     *
     * @return void
     */
    protected function createClass($className)
    {
        $classNameParts = explode('\\', $className);
        $class = array_pop($classNameParts);
        $fileContent = '<?php'
            . PHP_EOL . 'namespace ' . implode('\\', $classNameParts) . ';'
            . PHP_EOL . 'use ' . AbstractDependencyInjector::class . ';'
            . PHP_EOL . 'use ' . ContainerInterface::class . ';'
            . PHP_EOL . 'class ' . $class . ' extends AbstractDependencyInjector'
            . PHP_EOL . '{'
            . PHP_EOL . '}';

        $directoryParts = [
            $this->getBasePath(),
            implode(DIRECTORY_SEPARATOR, $classNameParts),
        ];
        $directory = implode(DIRECTORY_SEPARATOR,  $directoryParts);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $fileName = $directory . DIRECTORY_SEPARATOR . $class . '.php';

        $this->createdFiles[] = $fileName;
        file_put_contents($fileName, $fileContent);

        require_once $fileName;
    }

    /**
     * @return string
     */
    private function getBasePath()
    {
        $directoryParts = explode(DIRECTORY_SEPARATOR, __DIR__);
        $testsDirectoryPosition = array_search('tests', $directoryParts);

        $basePath = implode(DIRECTORY_SEPARATOR, array_slice($directoryParts, 0, $testsDirectoryPosition + 1));

        return $basePath;
    }

}
