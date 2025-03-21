<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\TwigComponents\Component\Event;

use Ibexa\Contracts\TwigComponents\ComponentInterface;
use Ibexa\TwigComponents\Component\Registry;
use Symfony\Contracts\EventDispatcher\Event;

final class RenderSingleEvent extends Event
{
    public const NAME = 'ibexa_twig_components.render_single';

    private Registry $registry;

    private string $groupName;

    private string $serviceId;

    /**
     * @var array<mixed>
     */
    private array $parameters;

    /**
     * @param array<mixed> $parameters
     */
    public function __construct(
        Registry $registry,
        string $groupName,
        string $serviceId,
        array $parameters = [],
    ) {
        $this->registry = $registry;
        $this->groupName = $groupName;
        $this->serviceId = $serviceId;
        $this->parameters = $parameters;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    public function getName(): string
    {
        return $this->serviceId;
    }

    public function getComponent(): ComponentInterface
    {
        $group = $this->registry->getComponents($this->getGroupName());

        return $group[$this->serviceId];
    }

    public function setComponent(ComponentInterface $component): void
    {
        $this->registry->addComponent($this->getGroupName(), $this->getName(), $component);
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
