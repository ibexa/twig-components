<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\TwigComponents\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TwigComponentCollector extends DataCollector
{
    private array $renderedComponents = [];

    private array $availableGroups = [];

    public function addRenderedComponent(string $group, string $name): void
    {
        $this->renderedComponents[] = compact('group', 'name');
    }

    public function addAvailableGroups(string $group): void
    {
        $this->availableGroups[] = compact('group');
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $this->data['rendered_components'] = $this->renderedComponents;
        $this->data['available_groups'] = $this->availableGroups;
    }

    public function reset(): void
    {
        $this->renderedComponents = [];
        $this->data = [];
    }

    public function getRenderedComponents(): array
    {
        return $this->data['rendered_components'] ?? [];
    }

    public function getAvailableGroups(): array
    {
        return $this->data['available_groups'] ?? [];
    }

    public function getName(): string
    {
        return 'ibexa.twig_components';
    }
}
