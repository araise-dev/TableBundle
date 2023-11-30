<?php

declare(strict_types=1);

namespace araise\TableBundle\Action;

use araise\CoreBundle\Action\Action as BaseAction;

class TableAction extends BaseAction
{
    public const OPT_VISIBILITY = 'visibility';

    public function __construct(string $acronym, array $options)
    {
        $this->defaultOptions[self::OPT_VISIBILITY] = true;
        $this->allowedTypes[self::OPT_VISIBILITY] = ['boolean', 'callable'];
        parent::__construct($acronym, $options);
    }

    public function getVisibility(?object $entity = null): bool
    {
        if (is_callable($this->getOption(self::OPT_VISIBILITY))) {
            return $this->getOption(self::OPT_VISIBILITY)($entity);
        }

        return $this->getOption(self::OPT_VISIBILITY);
    }
}
