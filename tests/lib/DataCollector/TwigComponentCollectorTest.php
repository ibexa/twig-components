<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\TwigComponents\DataCollector;

use Ibexa\TwigComponents\DataCollector\TwigComponentCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TwigComponentCollectorTest extends TestCase
{
    public function testAddRenderedComponentStoresData(): void
    {
        $collector = new TwigComponentCollector();
        $collector->addRenderedComponent('group1', 'component1');
        $collector->addRenderedComponent('group2', 'component2');

        $collector->collect(new Request(), new Response());

        self::assertSame(
            [
                ['group' => 'group1', 'name' => 'component1'],
                ['group' => 'group2', 'name' => 'component2'],
            ],
            $collector->getRenderedComponents()
        );
    }

    public function testAddAvailableGroupsStoresData(): void
    {
        $collector = new TwigComponentCollector();
        $collector->addAvailableGroups('group1');
        $collector->addAvailableGroups('group2');

        $collector->collect(new Request(), new Response());

        self::assertSame(
            [
                ['group' => 'group1'],
                ['group' => 'group2'],
            ],
            $collector->getAvailableGroups()
        );
    }
}
