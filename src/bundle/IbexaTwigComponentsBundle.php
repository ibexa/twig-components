<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\TwigComponents;

use Ibexa\Bundle\TwigComponents\DependencyInjection\Compiler\ComponentPass;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class IbexaTwigComponentsBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('twig')) {
            throw new LogicException('IbexaTwigComponentsBundle requires TwigBundle. Please enable it in your bundles.php file.');
        }

        if (!$container->hasExtension('twig_component')) {
            throw new LogicException('IbexaTwigComponentsBundle requires TwigComponentBundle (symfony/ux-twig-component). Please enable it in your bundles.php file.');
        }

        $container->addCompilerPass(new ComponentPass());
    }
}
