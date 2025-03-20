<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\TwigComponents\Component\Event;

use Ibexa\TwigComponents\Component\Registry;
use Symfony\Contracts\EventDispatcher\Event;

class RenderGroupEvent extends Event
{
    public const NAME = 'ibexa_twig_components.render_group';

    private Registry $registry;

    private string $groupName;

    /**
     * @var array<mixed>
     */
    private array $parameters;

    /**
     * @param array<mixed> $parameters
     */
    public function __construct(Registry $registry, string $groupName, array $parameters = [])
    {
        $this->registry = $registry;
        $this->groupName = $groupName;
        $this->parameters = $parameters;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    /**
     * @return \Ibexa\Contracts\TwigComponents\ComponentInterface[]
     */
    public function getComponents(): array
    {
        return $this->registry->getComponents($this->getGroupName());
    }

    /**
     * @param \Ibexa\Contracts\TwigComponents\ComponentInterface[] $components
     */
    public function setComponents(array $components): void
    {
        $this->registry->setComponents($this->getGroupName(), $components);
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
