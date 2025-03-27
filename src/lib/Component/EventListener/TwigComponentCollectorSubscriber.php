<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\TwigComponents\Component\EventListener;

use Ibexa\TwigComponents\Component\Event\RenderGroupEvent;
use Ibexa\TwigComponents\Component\Event\RenderSingleEvent;
use Ibexa\TwigComponents\DataCollector\TwigComponentCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TwigComponentCollectorSubscriber implements EventSubscriberInterface
{
    private TwigComponentCollector $collector;

    public function __construct(TwigComponentCollector $collector)
    {
        $this->collector = $collector;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RenderGroupEvent::NAME => ['onRenderGroup', 50],
            RenderSingleEvent::NAME => ['onRenderSingle', 50],
        ];
    }

    public function onRenderGroup(RenderGroupEvent $event): void
    {
        $this->collector->addAvailableGroups($event->getGroupName());
    }

    public function onRenderSingle(RenderSingleEvent $event): void
    {
        $this->collector->addRenderedComponent($event->getGroupName(), $event->getName());
    }
}
