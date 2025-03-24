<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\TwigComponents\DependencyInjection;

use Ibexa\Bundle\TwigComponents\DependencyInjection\IbexaTwigComponentsExtension;
use Ibexa\TwigComponents\Component\TwigComponent;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class IbexaTwigComponentsExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new IbexaTwigComponentsExtension()];
    }

    public function testRegistersTwigComponent(): void
    {
        $this->load([
            'test_group' => [
                'test_component' => [
                    'type' => 'template',
                    'arguments' => [
                        'template' => '@ibexa/template.html.twig',
                        'parameters' => ['title' => 'Test Title'],
                    ],
                ],
            ],
        ]);

        self::assertContainerBuilderHasService('test_component', TwigComponent::class);
        self::assertContainerBuilderHasServiceDefinitionWithTag(
            'test_component',
            'ibexa.twig.component',
            ['group' => 'test_group']
        );

        self::assertContainerBuilderHasServiceDefinitionWithArgument(
            'test_component',
            '$template',
            '@ibexa/template.html.twig'
        );
        self::assertContainerBuilderHasServiceDefinitionWithArgument(
            'test_component',
            '$parameters',
            ['title' => 'Test Title']
        );
    }

    public function testInvalidComponentTypeThrowsException(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Unrecognized option "invalid_component" under "ibexa_twig_components.ibexa_twig_components.invalid_group'
        );

        $this->load([
            'ibexa_twig_components' => [
                'invalid_group' => [
                    'invalid_component' => [
                        'type' => 'invalid_type',
                        'arguments' => [],
                    ],
                ],
            ],
        ]);
    }
}
