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

namespace whatwedo\TableBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use whatwedo\SearchBundle\Entity\Index;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Event\DataLoadEvent;

class SearchEventListener
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected array $kernelBundles
    ) {
    }

    public function searchResultSet(DataLoadEvent $event): void
    {
        if (! $event->getTable()->getDataLoader() instanceof DoctrineDataLoader
            || ! $event->getTable()->getSearchExtension()
            || ! $event->getTable()->getOption('searchable')) {
            return;
        }

        // Exec only if query is set
        $query = $event->getTable()->getSearchExtension()->getQuery();
        if (trim($query) === '') {
            return;
        }

        $queryBuilder = $event->getTable()->getDataLoader()->getOption(DoctrineDataLoader::OPTION_QUERY_BUILDER);
        $entity = $queryBuilder->getRootEntities()[0];
        $ids = $this->entityManager->getRepository(Index::class)->search($query, $entity);

        $queryBuilder
            ->andWhere(sprintf(
                '%s.id IN (:q_ids)',
                $queryBuilder->getRootAliases()[0]
            ))
            ->setParameter('q_ids', $ids);
    }
}
