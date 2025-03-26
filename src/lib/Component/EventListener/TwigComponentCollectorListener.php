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

final class TwigComponentCollectorListener
{
    private TwigComponentCollector $collector;

    public function __construct(TwigComponentCollector $collector)
    {
        $this->collector = $collector;
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
