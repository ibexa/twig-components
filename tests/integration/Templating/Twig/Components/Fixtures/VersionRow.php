<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\TwigComponents\Templating\Twig\Components\Fixtures;

final readonly class VersionRow
{
    public function __construct(
        public int $versionNo,
        public bool $canDelete,
    ) {
    }
}
