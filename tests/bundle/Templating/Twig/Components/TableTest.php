<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\TwigComponents\Templating\Twig\Components;

use Ibexa\Bundle\TwigComponents\Templating\Twig\Components\Table;
use PHPUnit\Framework\TestCase;

final class TableTest extends TestCase
{
    /**
     * @dataProvider provideTableClass
     */
    public function testTableClassAppliesModifierToBaseClasses(
        ?string $classProp,
        string $expectedTableClass
    ): void {
        $table = new Table();
        if ($classProp !== null) {
            $table->class = $classProp;
        }

        self::assertSame($expectedTableClass, $table->getTableClass());
    }

    /**
     * @return iterable<string, array{?string, string}>
     */
    public static function provideTableClass(): iterable
    {
        yield 'default modifier' => [
            null,
            'ibexa-table table ibexa-table--last-column-sticky',
        ];

        yield 'custom modifier replaces the default' => [
            'ibexa-table--draft-conflict mb-3',
            'ibexa-table table ibexa-table--draft-conflict mb-3',
        ];

        yield 'empty string clears the modifier' => [
            '',
            'ibexa-table table',
        ];
    }

    public function testFullDataIsNullWhenMountSiteDoesNotProvideIt(): void
    {
        $table = new Table();
        $table->mount([new \stdClass()]);

        self::assertNull($table->getFullData());
    }

    public function testFullDataIsExposedIndependentlyOfRenderedData(): void
    {
        $renderedPage = [new \stdClass()];
        $wholeDataset = [...$renderedPage, new \stdClass(), new \stdClass()];

        $table = new Table();
        $table->mount($renderedPage, $wholeDataset);

        self::assertSame($renderedPage, $table->getData());
        self::assertSame($wholeDataset, $table->getFullData());
    }

    public function testIdentityPropsDefaultToNull(): void
    {
        $table = new Table();

        self::assertNull($table->type);
        self::assertNull($table->variant);
        self::assertNull($table->headline);
    }

    /**
     * @dataProvider provideColumnOptions
     *
     * @param array{header_class?: string, cell_class?: string}|null $options null = argument omitted
     * @param array{header_class?: string, cell_class?: string} $expectedOptions
     */
    public function testAddColumnCarriesOptionsToColumn(?array $options, array $expectedOptions): void
    {
        $table = new Table();
        if ($options === null) {
            $table->addColumn(
                'checkbox',
                static fn (): string => 'Label',
                static fn (): string => 'Cell'
            );
        } else {
            $table->addColumn(
                'checkbox',
                static fn (): string => 'Label',
                static fn (): string => 'Cell',
                110,
                $options
            );
        }

        self::assertSame($expectedOptions, $table->getColumns()['checkbox']->options);
    }

    /**
     * @return iterable<string, array{?array{header_class?: string, cell_class?: string}, array{header_class?: string, cell_class?: string}}>
     */
    public static function provideColumnOptions(): iterable
    {
        yield 'passed options land on the column' => [
            ['header_class' => 'custom-header', 'cell_class' => 'custom-cell'],
            ['header_class' => 'custom-header', 'cell_class' => 'custom-cell'],
        ];

        yield 'omitted options default to an empty set' => [
            null,
            [],
        ];
    }
}
