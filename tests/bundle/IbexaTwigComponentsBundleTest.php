<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\TwigComponents;

use Ibexa\Bundle\TwigComponents\IbexaTwigComponentsBundle;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class IbexaTwigComponentsBundleTest extends TestCase
{
    public function testBuildThrowsExceptionIfTwigBundleIsNotEnabled(): void
    {
        $container = new ContainerBuilder();
        $bundle = new IbexaTwigComponentsBundle();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('IbexaTwigComponentsBundle requires TwigBundle. Please enable it in your bundles.php file.');

        $bundle->build($container);
    }

    public function testBuildThrowsExceptionIfTwigComponentBundleIsNotEnabled(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new class() extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'twig';
            }
        });

        $bundle = new IbexaTwigComponentsBundle();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('IbexaTwigComponentsBundle requires TwigComponentBundle (symfony/ux-twig-component). Please enable it in your bundles.php file.');

        $bundle->build($container);
    }

    public function testBuildSuccessIfBothBundlesAreEnabled(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new class() extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'twig';
            }
        });
        $container->registerExtension(new class() extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'twig_component';
            }
        });

        $bundle = new IbexaTwigComponentsBundle();
        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $found = false;
        foreach ($passes as $pass) {
            if ($pass instanceof \Ibexa\Bundle\TwigComponents\DependencyInjection\Compiler\ComponentPass) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'ComponentPass should be registered');
    }
}
