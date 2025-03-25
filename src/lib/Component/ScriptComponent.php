<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\TwigComponents\Component;

use Ibexa\Contracts\TwigComponents\ComponentInterface;
use Twig\Environment;

class ScriptComponent implements ComponentInterface
{
    protected Environment $twig;

    protected string $src;

    protected string $type;

    protected ?string $async;

    protected ?string $defer;

    protected ?string $crossorigin;

    protected ?string $integrity;

    public function __construct(
        Environment $twig,
        string $src,
        string $type = 'text/javascript',
        ?string $async = null,
        ?string $defer = null,
        ?string $crossorigin = null,
        ?string $integrity = null
    ) {
        $this->twig = $twig;
        $this->src = $src;
        $this->type = $type;
        $this->async = $async;
        $this->defer = $defer;
        $this->crossorigin = $crossorigin;
        $this->integrity = $integrity;
    }

    public static function getType(): string
    {
        return 'script';
    }

    /**
     * @param array<mixed> $parameters
     */
    public function render(array $parameters = []): string
    {
        return $this->twig->render('@ibexadesign/script.html.twig', [
            'src' => $this->src,
            'type' => $this->type,
            'async' => $this->async,
            'defer' => $this->defer,
            'crossorigin' => $this->crossorigin,
            'integrity' => $this->integrity,
        ] + $parameters);
    }
}
