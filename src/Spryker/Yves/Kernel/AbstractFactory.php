<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Kernel;

use Pyz\Yves\Application\Plugin\Pimple;
use Spryker\Client\Kernel\ClassResolver\Client\ClientResolver;
use Spryker\Shared\Kernel\ContainerGlobals;
use Spryker\Shared\Kernel\Dependency\Injector\DependencyInjector;
use Spryker\Shared\Kernel\Dependency\Injector\DependencyInjectorCollectionInterface;
use Spryker\Yves\Kernel\ClassResolver\DependencyInjector\DependencyInjectorResolver;
use Spryker\Yves\Kernel\ClassResolver\DependencyProvider\DependencyProviderResolver;
use Spryker\Yves\Kernel\Exception\Container\ContainerKeyNotFoundException;

abstract class AbstractFactory implements FactoryInterface
{

    /**
     * @var \Spryker\Yves\Kernel\Container $container
     */
    private $container;

    /**
     * @var \Spryker\Client\Kernel\AbstractClient
     */
    private $client;

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return \Spryker\Yves\Kernel\Container
     */
    protected function createContainer()
    {
        $containerGlobals = $this->createContainerGlobals();
        $container = new Container($containerGlobals->getContainerGlobals());

        return $container;
    }

    /**
     * @return \Spryker\Shared\Kernel\ContainerGlobals
     */
    protected function createContainerGlobals()
    {
        return new ContainerGlobals();
    }

    /**
     * @deprecated Use DependencyProvider instead
     *
     * @return \Generated\Client\Ide\AutoCompletion|\Spryker\Shared\Kernel\LocatorLocatorInterface
     */
    protected function getLocator()
    {
        return Locator::getInstance();
    }

    /**
     * @return \Spryker\Client\Kernel\AbstractClient
     */
    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = $this->resolveClient();
        }

        return $this->client;
    }

    /**
     * @return \Spryker\Client\Kernel\AbstractClient
     */
    protected function resolveClient()
    {
        return $this->createClientResolver()->resolve($this);
    }

    /**
     * @deprecated Use `createClientResolver()` instead
     *
     * @return \Spryker\Client\Kernel\ClassResolver\Client\ClientResolver
     */
    protected function getClientResolver()
    {
        return $this->createClientResolver();
    }

    /**
     * @return \Spryker\Client\Kernel\ClassResolver\Client\ClientResolver
     */
    protected function createClientResolver()
    {
        return new ClientResolver();
    }

    /**
     * @deprecated Use `$this->getProvidedDependency(ApplicationConstants::FORM_FACTORY)` to get the form factory.
     *
     * Ensure that you registered `Spryker\Shared\Application\ServiceProvider\FormFactoryServiceProvider`
     *
     * @return \Symfony\Component\Form\FormFactoryInterface
     */
    protected function getFormFactory()
    {
        return (new Pimple())->getApplication()['form.factory'];
    }

    /**
     * @param string $key
     *
     * @throws \Spryker\Yves\Kernel\Exception\Container\ContainerKeyNotFoundException
     *
     * @return mixed
     */
    protected function getProvidedDependency($key)
    {
        if ($this->container === null) {
            $this->container = $this->createContainerWithProvidedDependencies();
        }

        if ($this->container->offsetExists($key) === false) {
            throw new ContainerKeyNotFoundException($this, $key);
        }

        return $this->container[$key];
    }

    /**
     * @deprecated Use `createContainerWithProvidedDependencies()` instead
     *
     * @return \Spryker\Yves\Kernel\Container
     */
    protected function getContainerWithProvidedDependencies()
    {
        return $this->createContainerWithProvidedDependencies();
    }

    /**
     * @return \Spryker\Yves\Kernel\Container
     */
    protected function createContainerWithProvidedDependencies()
    {
        $container = $this->createContainer();
        $dependencyInjectorCollection = $this->resolveDependencyInjectorCollection();
        $dependencyInjector = $this->createDependencyInjector($dependencyInjectorCollection);
        $dependencyProvider = $this->resolveDependencyProvider();

        $container = $this->provideDependencies($dependencyProvider, $container);
        $container = $dependencyInjector->inject($container);

        return $container;
    }

    /**
     * @param \Spryker\Yves\Kernel\AbstractBundleDependencyProvider $dependencyProvider
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return \Spryker\Yves\Kernel\Container
     */
    protected function provideDependencies(AbstractBundleDependencyProvider $dependencyProvider, Container $container)
    {
        return $dependencyProvider->provideDependencies($container);
    }

    /**
     * @return \Spryker\Shared\Kernel\Dependency\Injector\DependencyInjectorCollectionInterface
     */
    protected function resolveDependencyInjectorCollection()
    {
        return $this->createDependencyInjectorResolver()->resolve($this);
    }

    /**
     * @deprecated Use `createDependencyInjector()` instead
     *
     * @param \Spryker\Shared\Kernel\Dependency\Injector\DependencyInjectorCollectionInterface $dependencyInjectorCollection
     *
     * @return \Spryker\Shared\Kernel\Dependency\Injector\DependencyInjector
     */
    protected function getDependencyInjector(DependencyInjectorCollectionInterface $dependencyInjectorCollection)
    {
        return $this->createDependencyInjector($dependencyInjectorCollection);
    }

    /**
     * @param \Spryker\Shared\Kernel\Dependency\Injector\DependencyInjectorCollectionInterface $dependencyInjectorCollection
     *
     * @return \Spryker\Shared\Kernel\Dependency\Injector\DependencyInjector
     */
    protected function createDependencyInjector(DependencyInjectorCollectionInterface $dependencyInjectorCollection)
    {
        return new DependencyInjector($dependencyInjectorCollection);
    }

    /**
     * @deprecated Use `createDependencyInjectorResolver()` instead
     *
     * @return \Spryker\Yves\Kernel\ClassResolver\DependencyInjector\DependencyInjectorResolver
     */
    protected function getDependencyInjectorResolver()
    {
        return $this->createDependencyInjectorResolver();
    }

    /**
     * @return \Spryker\Yves\Kernel\ClassResolver\DependencyInjector\DependencyInjectorResolver
     */
    protected function createDependencyInjectorResolver()
    {
        return new DependencyInjectorResolver();
    }

    /**
     * @return \Spryker\Yves\Kernel\AbstractBundleDependencyProvider
     */
    protected function resolveDependencyProvider()
    {
        return $this->createDependencyProviderResolver()->resolve($this);
    }

    /**
     * @deprecated Use `createDependencyProviderResolver()` instead
     *
     * @return \Spryker\Yves\Kernel\ClassResolver\DependencyProvider\DependencyProviderResolver
     */
    protected function getDependencyProviderResolver()
    {
        return $this->createDependencyProviderResolver();
    }

    /**
     * @return \Spryker\Yves\Kernel\ClassResolver\DependencyProvider\DependencyProviderResolver
     */
    protected function createDependencyProviderResolver()
    {
        return new DependencyProviderResolver();
    }

}
