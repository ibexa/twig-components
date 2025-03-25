<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\TwigComponents\Component\Renderer;

use Ibexa\Bundle\TwigComponents\DataCollector\TwigComponentCollector;
use Ibexa\Contracts\TwigComponents\Exception\InvalidArgumentException;
use Ibexa\Contracts\TwigComponents\Renderer\RendererInterface;
use Ibexa\TwigComponents\Component\Event\RenderGroupEvent;
use Ibexa\TwigComponents\Component\Event\RenderSingleEvent;
use Ibexa\TwigComponents\Component\Registry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class DefaultRenderer implements RendererInterface
{
    private Registry $registry;

    private EventDispatcherInterface $eventDispatcher;

    private TwigComponentCollector $collector;

    public function __construct(
        Registry $registry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param array<mixed> $parameters
     *
     * @return string[]
     */
    public function renderGroup(string $groupName, array $parameters = []): array
    {
        $this->eventDispatcher->dispatch(new RenderGroupEvent(
            $this->registry,
            $groupName,
            $parameters
        ), RenderGroupEvent::NAME);

        $components = $this->registry->getComponents($groupName);

        $rendered = [];
        foreach ($components as $id => $component) {
            $rendered[] = $this->renderSingle($groupName, $id, $parameters);
        }

        return $rendered;
    }

    /**
     * @param array<mixed> $parameters
     */
    public function renderSingle(string $groupName, string $name, array $parameters = []): string
    {
        $this->eventDispatcher->dispatch(new RenderSingleEvent(
            $this->registry,
            $groupName,
            $name,
            $parameters
        ), RenderSingleEvent::NAME);

        $components = $this->registry->getComponents($groupName);

        if (!isset($components[$name])) {
            throw new InvalidArgumentException(
                'id',
                sprintf("Can't find Component '%s' in group '%s'", $name, $groupName),
            );
        }

        return $components[$name]->render($parameters);
    }
}
