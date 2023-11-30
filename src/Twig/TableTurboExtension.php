<?php

declare(strict_types=1);

namespace araise\TableBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TableTurboExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        protected bool $turboEnabled
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'araise_table_turbo_enabled' => $this->turboEnabled,
        ];
    }
}
