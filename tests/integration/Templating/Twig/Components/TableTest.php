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
use Symfony\UX\TwigComponent\Event\PostMountEvent;
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

        $html = $rendered->toString();
        self::assertStringContainsString('ibexa-table', $html);
        $this->assertMatchesSnapshot($html, 'table_renders');
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
            ],
        );

        $this->assertMatchesSnapshot($rendered->toString(), 'table_empty_state');
    }

    private function assertMatchesSnapshot(string $actual, string $snapshotName): void
    {
        $snapshotPath = __DIR__ . '/__snapshots__/' . $snapshotName . '.html';

        if (!file_exists($snapshotPath)) {
            file_put_contents($snapshotPath, $actual);
            self::markTestIncomplete('Snapshot created: ' . $snapshotPath);
        }

        $expected = file_get_contents($snapshotPath);

        // Normalize whitespace for easier comparison if needed, or just compare strictly
        self::assertSame($expected, $actual, 'Snapshot comparison failed for ' . $snapshotName);
    }

    public function testTableComponentAllowsAddingColumnsViaEvent(): void
    {
        $dispatcher = self::getIbexaTestCore()->getServiceByClassName(EventDispatcherInterface::class);
        $listener = static function (PreMountEvent $event): void {
            $component = $event->getComponent();
            if (!$component instanceof Table) {
                return;
            }

            $component->addColumn(
                'extra_column',
                static fn (): string => 'Extra Column Label',
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
        $this->assertMatchesSnapshot($html, 'table_with_extra_columns');

        $dispatcher->removeListener(PreMountEvent::class, $listener);
    }

    public function testTableComponentRendersHeadline(): void
    {
        $rendered = $this->renderTwigComponent(
            name: 'ibexa.Table',
            data: [
                'data' => [],
                'headline' => 'Open drafts',
            ],
        );

        $html = $rendered->toString();
        self::assertStringContainsString('<div class="ibexa-table-header">', $html);
        self::assertStringContainsString('<div class="ibexa-table-header__headline">Open drafts</div>', $html);
        $this->assertMatchesSnapshot($html, 'table_with_headline');
    }

    public function testTableComponentHeadlineCanBeOverriddenByListener(): void
    {
        $dispatcher = self::getIbexaTestCore()->getServiceByClassName(EventDispatcherInterface::class);
        $listener = static function (PostMountEvent $event): void {
            $component = $event->getComponent();
            if (!$component instanceof Table) {
                return;
            }

            $component->headline = 'Headline from listener';
        };
        $dispatcher->addListener(PostMountEvent::class, $listener);

        try {
            $html = $this
                ->renderTwigComponent(
                    name: 'ibexa.Table',
                    data: [
                        'data' => [],
                        'headline' => 'Default headline',
                    ],
                )
                ->toString();
        } finally {
            $dispatcher->removeListener(PostMountEvent::class, $listener);
        }

        self::assertStringContainsString('Headline from listener', $html);
        self::assertStringNotContainsString('Default headline', $html);
    }

    public function testTableComponentAppliesCustomTableClass(): void
    {
        $html = $this
            ->renderTwigComponent(
                name: 'ibexa.Table',
                data: [
                    'data' => [],
                    'class' => 'ibexa-table--draft-conflict',
                ],
            )
            ->toString();

        self::assertStringContainsString('<table class="ibexa-table table ibexa-table--draft-conflict">', $html);
        self::assertStringNotContainsString('ibexa-table--last-column-sticky', $html);
    }

    public function testTableComponentRendersColumnOptionClasses(): void
    {
        $dispatcher = self::getIbexaTestCore()->getServiceByClassName(EventDispatcherInterface::class);
        $listener = static function (PostMountEvent $event): void {
            $component = $event->getComponent();
            if (!$component instanceof Table) {
                return;
            }

            $component->addColumn(
                'checkbox',
                static fn (): string => 'Checkbox',
                static fn (): string => 'X',
                110,
                ['header_class' => 'ibexa-table__header-cell--checkbox', 'cell_class' => 'ibexa-table__cell--checkbox']
            );
        };
        $dispatcher->addListener(PostMountEvent::class, $listener);

        try {
            $html = $this
                ->renderTwigComponent(
                    name: 'ibexa.Table',
                    data: [
                        'data' => [new \stdClass()],
                    ],
                )
                ->toString();
        } finally {
            $dispatcher->removeListener(PostMountEvent::class, $listener);
        }

        self::assertStringContainsString('ibexa-table__header-cell--checkbox', $html);
        self::assertStringContainsString('ibexa-table__cell--checkbox', $html);
    }

    public function testListenersCanGuardOnTypeAndUseFullData(): void
    {
        $dispatcher = self::getIbexaTestCore()->getServiceByClassName(EventDispatcherInterface::class);
        $listener = static function (PostMountEvent $event): void {
            $component = $event->getComponent();
            if (!$component instanceof Table || $component->type !== 'versions') {
                return;
            }

            $datasetSize = iterator_count(new \ArrayIterator([...$component->getFullData() ?? $component->getData()]));
            $component->addColumn(
                'dataset_size',
                static fn (): string => sprintf('Dataset of %d (%s)', $datasetSize, $component->variant),
                static fn (): string => '-'
            );
        };
        $dispatcher->addListener(PostMountEvent::class, $listener);

        try {
            $renderedPage = [new \stdClass()];
            $html = $this
                ->renderTwigComponent(
                    name: 'ibexa.Table',
                    data: [
                        'data' => $renderedPage,
                        'fullData' => [...$renderedPage, new \stdClass(), new \stdClass()],
                        'type' => 'versions',
                        'variant' => 'draft',
                    ],
                )
                ->toString();

            $unguardedHtml = $this
                ->renderTwigComponent(
                    name: 'ibexa.Table',
                    data: [
                        'data' => $renderedPage,
                    ],
                )
                ->toString();
        } finally {
            $dispatcher->removeListener(PostMountEvent::class, $listener);
        }

        self::assertStringContainsString('Dataset of 3 (draft)', $html);
        self::assertStringNotContainsString('Dataset of', $unguardedHtml);
    }

    public function testTableComponentRespectsColumnPriorityViaEvent(): void
    {
        $dispatcher = self::getIbexaTestCore()->getServiceByClassName(EventDispatcherInterface::class);
        $listener = static function (PreMountEvent $event): void {
            $component = $event->getComponent();
            if (!$component instanceof Table) {
                return;
            }

            $component->addColumn(
                'low_priority',
                static fn (): string => 'Low Priority Column',
                static fn (): string => 'Low',
                10
            );
            $component->addColumn(
                'high_priority',
                static fn (): string => 'High Priority Column',
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
        self::assertGreaterThan(
            strpos($html, 'High Priority Column'),
            strpos($html, 'Low Priority Column'),
            'High Priority Column should come before Low Priority Column'
        );
        $this->assertMatchesSnapshot($html, 'table_column_priority');

        $dispatcher->removeListener(PreMountEvent::class, $listener);
    }
}
