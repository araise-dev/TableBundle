<?php

declare(strict_types=1);
/*
 * Copyright (c) 2017, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace araise\TableBundle\Factory;

use araise\CoreBundle\Manager\FormatterManager;
use araise\TableBundle\DataLoader\ArrayDataLoader;
use araise\TableBundle\DataLoader\DataLoaderInterface;
use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\DataLoader\DoctrineTreeDataLoader;
use araise\TableBundle\Extension\ExtensionInterface;
use araise\TableBundle\Table\Table;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class TableFactory implements ServiceSubscriberInterface
{
    protected array $extensions = [];

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected RequestStack $requestStack,
        protected FormatterManager $formatterManager,
        protected ContainerInterface $locator
    ) {
    }

    public function create($identifier, ?string $dataLoader = null, $options = []): Table
    {
        if (! $dataLoader) {
            $dataLoader = DoctrineDataLoader::class;
        }

        /** @var DataLoaderInterface $dataLoaderInstance */
        $dataLoaderInstance = $this->locator->get($dataLoader);
        $dataLoaderInstance->setOptions($options[Table::OPT_DATALOADER_OPTIONS]);
        $extensions = $this->getExtensions();
        $dataLoaderInstance->loadNecessaryExtensions($extensions);
        $options[Table::OPT_DATA_LOADER] = $dataLoaderInstance;

        if ($options[Table::OPT_DATA_LOADER] instanceof DoctrineDataLoader && ! isset($options[Table::OPT_SEARCHABLE])) {
            $options[Table::OPT_SEARCHABLE] = true;
        }

        return new Table(
            $identifier,
            $options,
            $this->eventDispatcher,
            $extensions,
            $this->formatterManager
        );
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->extensions[$extension::class] = $extension;
    }

    public static function getSubscribedServices(): array
    {
        return [
            ArrayDataLoader::class,
            DoctrineDataLoader::class,
            DoctrineTreeDataLoader::class,
        ];
    }

    protected function getExtensions(): array
    {
        $newExtensions = [];
        foreach ($this->extensions as $extension) {
            $newExtensions[$extension::class] = clone $extension;
        }
        return $newExtensions;
    }
}
