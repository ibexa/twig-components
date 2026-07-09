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
    public function testTableClassDefaultsToLastColumnSticky(): void
    {
        $table = new Table();
        $table->mount();

        self::assertSame('ibexa-table table ibexa-table--last-column-sticky', $table->getTableClass());
    }

    public function testTableClassReplacesModifierWhenClassPropIsSet(): void
    {
        $table = new Table();
        $table->class = 'ibexa-table--draft-conflict mb-3';
        $table->mount();

        self::assertSame('ibexa-table table ibexa-table--draft-conflict mb-3', $table->getTableClass());
    }

    public function testFullDataFallsBackToRenderedData(): void
    {
        $rows = [new \stdClass()];

        $table = new Table();
        $table->mount($rows);

        self::assertSame($rows, $table->getFullData());
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
        $table->mount();

        self::assertNull($table->type);
        self::assertNull($table->variant);
        self::assertNull($table->headline);
    }

    public function testAddColumnCarriesOptionsToColumn(): void
    {
        $table = new Table();
        $table->mount();
        $table->addColumn(
            'checkbox',
            static fn (): string => 'Label',
            static fn (): string => 'Cell',
            110,
            ['header_class' => 'custom-header', 'cell_class' => 'custom-cell']
        );

        $column = $table->getColumns()['checkbox'];

        self::assertSame(
            ['header_class' => 'custom-header', 'cell_class' => 'custom-cell'],
            $column->options
        );
    }

    public function testAddColumnDefaultsToEmptyOptions(): void
    {
        $table = new Table();
        $table->mount();
        $table->addColumn(
            'plain',
            static fn (): string => 'Label',
            static fn (): string => 'Cell'
        );

        self::assertSame([], $table->getColumns()['plain']->options);
    }
}
