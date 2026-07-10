<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\TwigComponents;

use Ibexa\Bundle\DesignEngine\IbexaDesignEngineBundle;
use Ibexa\Bundle\TwigComponents\IbexaTwigComponentsBundle;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\Test\Core\IbexaTestKernel;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Twig\Environment;

final class TwigComponentsIbexaTestKernel extends IbexaTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();
        yield new TwigComponentBundle();
        yield new IbexaDesignEngineBundle();
        yield new IbexaTwigComponentsBundle();
    }

    protected static function getExposedServicesByClass(): iterable
    {
        yield SiteAccessServiceInterface::class;
        yield ConfigResolverInterface::class;
        yield EventDispatcherInterface::class;
        yield Environment::class;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        $loader->load(__DIR__ . '/Resources/ibexa_test_config.yaml');
    }
}
