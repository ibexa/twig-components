<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\TwigComponents\DependencyInjection;

use Ibexa\Bundle\TwigComponents\DependencyInjection\Compiler\ComponentPass;
use Ibexa\TwigComponents\Exception\InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

final class IbexaTwigComponentsExtension extends Extension implements PrependExtensionInterface
{
    private const COMPONENT_MAP = [
        'script' => 'Ibexa\TwigComponents\Component\ScriptComponent',
        'stylesheet' => 'Ibexa\TwigComponents\Component\LinkComponent',
        'template' => 'Ibexa\TwigComponents\Component\TwigComponent',
        'controller' => 'Ibexa\TwigComponents\Component\ControllerComponent',
        'html' => 'Ibexa\TwigComponents\Component\HtmlComponent',
    ];

    public const EXTENSION_NAME = 'ibexa_twig_components';

    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');

        if ($this->shouldLoadTestServices($container)) {
            $loader->load('test/pages.yaml');
            $loader->load('test/components.yaml');
            $loader->load('test/contexts.yaml');
        }

        $configuration = $this->processConfiguration(new Configuration(), $configs);
        $this->registerConfiguredComponents($configuration, $container);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDefaultConfiguration($container);
        $this->prependJMSTranslation($container);
    }

    private function prependDefaultConfiguration(ContainerBuilder $container): void
    {
        $configFile = __DIR__ . '/../Resources/config/prepend.yaml';

        $container->addResource(new FileResource($configFile));

        $configs = Yaml::parseFile($configFile, Yaml::PARSE_CONSTANT) ?? [];
        foreach ($configs as $name => $config) {
            $container->prependExtensionConfig($name, $config);
        }
    }

    private function prependJMSTranslation(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('jms_translation', [
            'configs' => [
                self::EXTENSION_NAME => [
                    'dirs' => [
                        __DIR__ . '/../../',
                    ],
                    'excluded_dirs' => ['Behat'],
                    'output_dir' => __DIR__ . '/../Resources/translations/',
                    'output_format' => 'xliff',
                ],
            ],
        ]);
    }

    private function shouldLoadTestServices(ContainerBuilder $container): bool
    {
        return $container->hasParameter('ibexa.behat.browser.enabled')
            && true === $container->getParameter('ibexa.behat.browser.enabled');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerConfiguredComponents(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $group => $components) {
            foreach ($components as $name => $componentConfig) {
                $type = $componentConfig['type'] ?? null;
                if (!isset(self::COMPONENT_MAP[$type])) {
                    throw new InvalidArgumentException($type, sprintf('Invalid component type "%s" for component "%s"', $type, $name));
                }
                $className = self::COMPONENT_MAP[$type];

                $arguments = $componentConfig['arguments'];
                $modifiedArguments = array_combine(
                    array_map(static fn ($key) => '$' . $key, array_keys($arguments)),
                    array_values($arguments)
                );

                $definition = new Definition($className);
                $definition->setArguments($modifiedArguments);
                $definition->setLazy(true);
                $definition->addTag(ComponentPass::TAG_NAME, ['group' => $group]);

                $container->setDefinition($name, $definition);
            }
        }
    }
}
