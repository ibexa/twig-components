<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\TwigComponents\Templating\Twig\Components;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ChainConfigResolver;
use Ibexa\Bundle\TwigComponents\Templating\Twig\Components\Table;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\Test\Core\IbexaKernelTestCase;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

final class TableTest extends IbexaKernelTestCase
{
    use InteractsWithTwigComponents;

    protected function setUp(): void
    {
        self::bootKernel();

        $siteAccessService = self::getIbexaTestCore()->getServiceByClassName(SiteAccessServiceInterface::class);
        assert($siteAccessService instanceof SiteAccessAware);
        $siteAccess = $siteAccessService->get('admin');

        $configResolver = self::getIbexaTestCore()->getServiceByClassName(ConfigResolverInterface::class);
        self::assertInstanceOf(ChainConfigResolver::class, $configResolver);

        foreach ($configResolver->getAllResolvers() as $resolver) {
            if ($resolver instanceof SiteAccessAware) {
                $resolver->setSiteAccess($siteAccess);
            }
        }
    }

    public function testTableComponentMounts(): void
    {
        $component = $this->mountTwigComponent(
            name: 'ibexa.Table',
            data: [
                'data' => [],
            ],
        );

        self::assertInstanceOf(Table::class, $component);
    }

    public function testTableComponentRenders(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'ibexa.Table',
            data: [
                'data' => [],
            ],
        );

        self::assertStringContainsString('ibexa-table', $rendered->toString());
    }

    public function testTableComponentInfersDataType(): void
    {
        $component = $this->mountTwigComponent(
            name: 'ibexa.Table',
            data: [
                'data' => [new \stdClass(), new \stdClass()],
            ],
        );

        self::assertInstanceOf(Table::class, $component);

        self::assertSame(\stdClass::class, $component->getDataType());
    }

    public function testTableComponentRendersEmptyState(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'ibexa.Table',
            data: [
                'data' => [],
                'emptyStateTitle' => new TranslatableMessage('Custom Title', [], 'messages'),
                'emptyStateDescription' => new TranslatableMessage('Custom Description', [], 'messages'),
            ],
        );

        $html = $rendered->toString();
        self::assertStringContainsString('ibexa-empty-state', $html);
        self::assertStringContainsString('Custom Title', $html);
        self::assertStringContainsString('Custom Description', $html);
    }

    public function testTableComponentRespectsPreMountEvent(): void
    {
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $listener = static function (PreMountEvent $event): void {
            if (!$event->getComponent() instanceof Table) {
                return;
            }

            $data = $event->getData();
            $data['emptyStateTitle'] = new TranslatableMessage('Overridden Title', [], 'messages');
            $data['emptyStateDescription'] = new TranslatableMessage('Overridden Description', [], 'messages');
            $event->setData($data);
        };
        $dispatcher->addListener(PreMountEvent::class, $listener);

        $rendered = $this->renderTwigComponent(
            name: 'ibexa.Table',
            data: [
                'data' => [],
                'emptyStateTitle' => new TranslatableMessage('Original Title', [], 'ibexa_search'),
                'emptyStateDescription' => new TranslatableMessage('Original Description', [], 'ibexa_search'),
            ],
        );

        $html = $rendered->toString();
        self::assertStringContainsString('Overridden Title', $html);
        self::assertStringNotContainsString('Original Title', $html);
        self::assertStringContainsString('Overridden Description', $html);
        self::assertStringNotContainsString('Original Description', $html);

        $dispatcher->removeListener(PreMountEvent::class, $listener);
    }

    public function testTableComponentAllowsAddingColumnsViaEvent(): void
    {
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $listener = static function (PreMountEvent $event): void {
            $component = $event->getComponent();
            if (!$component instanceof Table) {
                return;
            }

            $component->addColumn(
                'extra_column',
                'Extra Column Label',
                static fn (object $item): string => 'Value: ' . ($item->name ?? 'unknown')
            );
        };
        $dispatcher->addListener(PreMountEvent::class, $listener);

        $item = new \stdClass();
        $item->name = 'Foo';

        $rendered = $this->renderTwigComponent(
            name: 'ibexa.Table',
            data: [
                'data' => [$item],
            ],
        );

        $html = $rendered->toString();
        self::assertStringContainsString('Extra Column Label', $html);
        self::assertStringContainsString('Value: Foo', $html);

        $dispatcher->removeListener(PreMountEvent::class, $listener);
    }

    public function testTableComponentRespectsColumnPriorityViaEvent(): void
    {
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $listener = static function (PreMountEvent $event): void {
            $component = $event->getComponent();
            if (!$component instanceof Table) {
                return;
            }

            $component->addColumn(
                'low_priority',
                'Low Priority Column',
                static fn (): string => 'Low',
                10
            );
            $component->addColumn(
                'high_priority',
                'High Priority Column',
                static fn (): string => 'High',
                100
            );
        };
        $dispatcher->addListener(PreMountEvent::class, $listener);

        $rendered = $this->renderTwigComponent(
            name: 'ibexa.Table',
            data: [
                'data' => [new \stdClass()],
            ],
        );

        $html = $rendered->toString();
        // High Priority Column should come before Low Priority Column
        self::assertGreaterThan(
            strpos($html, 'High Priority Column'),
            strpos($html, 'Low Priority Column')
        );

        $dispatcher->removeListener(PreMountEvent::class, $listener);
    }
}
