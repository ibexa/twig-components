<?php

namespace Ibexa\Tests\Bundle\TwigComponents\Component;

use Ibexa\TwigComponents\Component\TemplateComponent;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

final class TemplateComponentTest extends TestCase
{

    public function testRenderWithParameters(): void
    {
        $twig = $this->configureTwig([
            '__parameter__' => false,
        ]);

        $component = new TemplateComponent(
            $twig,
            '__template__',
            [
                '__parameter__' => true,
            ],
        );

        $component->render([
            '__parameter__' => false,
        ]);
    }

    public function testRenderWithoutParameters(): void
    {
        $twig = $this->configureTwig([
            '__parameter__' => true,
        ]);

        $component = new TemplateComponent(
            $twig,
            '__template__',
            [
                '__parameter__' => true,
            ],
        );

        $component->render();
    }

    /**
     * @param array<mixed> $parameters
     *
     * @return \Twig\Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    private function configureTwig(array $parameters): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '__template__',
                $parameters,
            );

        return $twig;
    }
}
