<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\TwigComponents\Templating\Twig\Components;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ChainConfigResolver;
use Ibexa\Bundle\TwigComponents\Templating\Twig\Components\Table;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\Test\Core\IbexaKernelTestCase;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Ibexa\Tests\Integration\TwigComponents\FormAwareTwigComponentsIbexaTestKernel;
use Ibexa\Tests\Integration\TwigComponents\Templating\Twig\Components\Fixtures\VersionRow;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Twig\Environment;

/**
 * Proves the admin-ui versions-table pattern: a column cell closure rendering
 * form_widget() via TemplateWrapper::renderBlock(), with the component wrapped
 * in form_start()/form_end(). The shared FormRenderer must mark widgets rendered
 * so form_end() does not duplicate them.
 */
final class TableFormWidgetRenderingTest extends IbexaKernelTestCase
{
    protected static function getKernelClass(): string
    {
        return FormAwareTwigComponentsIbexaTestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $siteAccessService = self::getIbexaTestCore()->getServiceByClassName(SiteAccessServiceInterface::class);
        assert($siteAccessService instanceof SiteAccessAware);
        $siteAccess = $siteAccessService->get('admin');

        $configResolver = self::getIbexaTestCore()->getServiceByClassName(ConfigResolverInterface::class);
        self::assertInstanceOf(ChainConfigResolver::class, $configResolver);

        foreach ($configResolver->getAllResolvers() as $resolver) {
            if ($resolver instanceof SiteAccessAware) {
                $resolver->setSiteAccess($siteAccess);
            }
        }
    }

    public function testFormWidgetCellIsRenderedOncePerRowAndNotDuplicatedByFormEnd(): void
    {
        $dispatcher = self::getIbexaTestCore()->getServiceByClassName(EventDispatcherInterface::class);
        $twig = self::getIbexaTestCore()->getServiceByClassName(Environment::class);
        $formFactory = self::getIbexaTestCore()->getServiceByClassName(FormFactoryInterface::class);

        $versionsBuilder = $formFactory
            ->createNamedBuilder('version_remove', FormType::class)
            ->add('versions', FormType::class);
        $versionsBuilder->get('versions')
            ->add('1', CheckboxType::class, ['required' => false])
            ->add('2', CheckboxType::class, ['required' => false]);
        $formView = $versionsBuilder->getForm()->createView();

        $cellTemplate = $twig->createTemplate(
            '{% block checkbox_cell %}{{ form_widget(form.versions[version_no], {attr: {disabled: not can_delete}}) }}{% endblock %}'
        );

        $listener = static function (PostMountEvent $event) use ($cellTemplate, $formView): void {
            $component = $event->getComponent();
            if (!$component instanceof Table) {
                return;
            }

            $component->addColumn(
                'checkbox',
                static fn (): string => 'Checkbox Column Label',
                static fn (VersionRow $item): string => $cellTemplate->renderBlock('checkbox_cell', [
                    'form' => $formView,
                    'version_no' => (string)$item->versionNo,
                    'can_delete' => $item->canDelete,
                ]),
                110
            );
        };
        $dispatcher->addListener(PostMountEvent::class, $listener);

        try {
            $deletableVersion = new VersionRow(versionNo: 1, canDelete: true);
            $lockedVersion = new VersionRow(versionNo: 2, canDelete: false);

            $html = $twig
                ->createTemplate(
                    '{{ form_start(form) }}'
                    . '{% component "ibexa.Table" with { data: rows } %}{% endcomponent %}'
                    . '{{ form_end(form) }}'
                )
                ->render(['form' => $formView, 'rows' => [$deletableVersion, $lockedVersion]]);
        } finally {
            $dispatcher->removeListener(PostMountEvent::class, $listener);
        }

        self::assertStringContainsString('Checkbox Column Label', $html);
        self::assertSame(
            1,
            substr_count($html, 'name="version_remove[versions][1]"'),
            'Widget for version 1 must be rendered exactly once (no form_end duplication)'
        );
        self::assertSame(
            1,
            substr_count($html, 'name="version_remove[versions][2]"'),
            'Widget for version 2 must be rendered exactly once (no form_end duplication)'
        );
        self::assertDoesNotMatchRegularExpression(
            '/<input[^>]*name="version_remove\[versions\]\[1\]"[^>]*disabled/',
            $html,
            'Deletable version checkbox must not be disabled'
        );
        self::assertMatchesRegularExpression(
            '/<input[^>]*name="version_remove\[versions\]\[2\]"[^>]*disabled/',
            $html,
            'Non-deletable version checkbox must carry the disabled attribute'
        );
    }
}
