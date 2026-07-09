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
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * Variant of {@see TwigComponentsIbexaTestKernel} with Symfony Forms enabled, for tests
 * covering form widgets rendered inside table column closures.
 */
final class FormAwareTwigComponentsIbexaTestKernel extends IbexaTestKernel
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
        yield FormFactoryInterface::class;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        $loader->load(__DIR__ . '/Resources/ibexa_test_config.yaml');
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'form' => ['csrf_protection' => false],
            ]);

            // TwigBundle skips its form integration when symfony/form is a dev-only
            // dependency (ContainerBuilder::willBeAvailable()), so mirror
            // twig-bundle/Resources/config/form.php here; ExtensionPass picks these
            // definitions up and wires the twig.extension tag + theme paths.
            $container->setParameter('twig.form.resources', ['form_div_layout.html.twig']);
            $container->register('twig.extension.form', FormExtension::class)
                ->setArguments([
                    new Reference('translator', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                ]);
            $container->register('twig.form.engine', TwigRendererEngine::class)
                ->setArguments(['%twig.form.resources%', new Reference('twig')]);
            $container->register('twig.form.renderer', FormRenderer::class)
                ->setArguments([
                    new Reference('twig.form.engine'),
                    new Reference('security.csrf.token_manager', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                ])
                ->addTag('twig.runtime');
        });
    }
}
