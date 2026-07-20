<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\TwigComponents\Templating\Twig\Components;

use Ibexa\Bundle\TwigComponents\Templating\Twig\Components\Table\Column;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @phpstan-import-type ColumnOptions from Column
 */
#[AsTwigComponent(
    name: 'ibexa.Table',
    template: '@ibexadesign/twig_components/table.html.twig',
)]
final class Table
{
    private const string BASE_CLASS = 'ibexa-table table';

    /**
     * Identifies the table to PostMount listeners. Guard on this instead of
     * {@see getDataType()} when different tables share a row type or may be empty.
     */
    public ?string $type = null;

    /** Sub-flavour of a table type (e.g. "draft"/"published"/"archived" for one type). */
    public ?string $variant = null;

    /**
     * Rendered as the table header when non-null; PostMount listeners may overwrite it
     * to replace the headline of a table they contribute columns to.
     */
    public ?string $headline = null;

    /**
     * Modifier CSS classes for the <table> element; pass '' to render the bare base classes.
     * The "ibexa-table table" base is always applied.
     */
    public string $class = 'ibexa-table--last-column-sticky';

    /** @var iterable<object> */
    #[ExposeInTemplate]
    private iterable $data = [];

    /** @var iterable<object>|null */
    private ?iterable $fullData = null;

    /** @var class-string|null */
    private ?string $dataType = null;

    /** @var array<string, mixed> */
    public array $parameters = [];

    /** @var array<string, Column> */
    private array $columns = [];

    /** @var array<string, Column>|null */
    private ?array $orderedColumns = null;

    /**
     * @param iterable<object> $data rows rendered by the table (e.g. the current pagination page)
     * @param iterable<object>|null $fullData whole dataset the rendered rows were taken from;
     *        lets listeners decide whether their column applies independently of pagination
     */
    public function mount(iterable $data = [], ?iterable $fullData = null): void
    {
        if ($data !== []) {
            $this->data = $data;
        }

        $this->fullData = $fullData;
    }

    /**
     * @return iterable<object>
     */
    public function getData(): iterable
    {
        return $this->data;
    }

    /**
     * Whole dataset the rendered rows were taken from, or null when the mount site
     * did not provide one — callers decide explicitly whether falling back to
     * {@see getData()} (page-scoped rows) is acceptable for their use case.
     *
     * @return iterable<object>|null
     */
    public function getFullData(): ?iterable
    {
        return $this->fullData;
    }

    #[ExposeInTemplate('table_class')]
    public function getTableClass(): string
    {
        return trim(self::BASE_CLASS . ' ' . $this->class);
    }

    /**
     * @return class-string|null
     */
    public function getDataType(): ?string
    {
        return $this->dataType ??= $this->resolveDataType();
    }

    /**
     * @return array<string, Column>
     */
    #[ExposeInTemplate('columns')]
    public function getColumns(): array
    {
        return $this->orderedColumns ??= $this->orderColumns();
    }

    /**
     * @return array<string, Column>
     */
    private function orderColumns(): array
    {
        $columns = $this->columns;

        uasort($columns, static fn (Column $a, Column $b): int => $b->priority <=> $a->priority);

        return $columns;
    }

    /**
     * @phpstan-param callable(Column): string $label
     * @phpstan-param callable(mixed, Column): string $renderer
     * @phpstan-param ColumnOptions $options presentation hints, see {@see Column::__construct()}
     */
    public function addColumn(
        string $identifier,
        callable $label,
        callable $renderer,
        int $priority = 0,
        array $options = []
    ): self {
        $this->orderedColumns = null;
        $this->columns[$identifier] = new Column(
            $identifier,
            $label(...),
            $renderer(...),
            $priority,
            $options,
        );

        return $this;
    }

    public function removeColumn(string $identifier): self
    {
        $this->orderedColumns = null;
        unset($this->columns[$identifier]);

        return $this;
    }

    /**
     * @phpstan-return class-string|null
     */
    private function resolveDataType(): ?string
    {
        $firstItem = null;
        foreach ($this->data as $item) {
            $firstItem = $item;
            break;
        }

        if (!is_object($firstItem)) {
            return null;
        }

        $candidates = array_merge(
            [get_class($firstItem)],
            class_parents($firstItem),
            class_implements($firstItem)
        );

        foreach ($this->data as $item) {
            $candidates = array_filter($candidates, static fn ($candidate): bool => $item instanceof $candidate);
            if (empty($candidates)) {
                return null;
            }
        }

        /** @var class-string|null $inferredType */
        $inferredType = reset($candidates);

        return $inferredType;
    }

    public function renderCell(Column $column, mixed $item): string
    {
        return ($column->renderer)($item, $column);
    }

    public function renderLabel(Column $column): string
    {
        return ($column->label)($column);
    }
}
