<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\TwigComponents\Component;

use Ibexa\Contracts\TwigComponents\ComponentInterface;
use Twig\Environment;

class TwigComponent implements ComponentInterface
{
    protected Environment $twig;

    protected string $template;

    /**
     * @var array<mixed>
     */
    protected array $parameters;

    /**
     * @param array<mixed> $parameters
     */
    public function __construct(
        Environment $twig,
        string $template,
        array $parameters = []
    ) {
        $this->twig = $twig;
        $this->template = $template;
        $this->parameters = $parameters;
    }

    /**
     * @param array<mixed> $parameters
     */
    public function render(array $parameters = []): string
    {
        return $this->twig->render($this->template, $parameters + $this->parameters);
    }
}
