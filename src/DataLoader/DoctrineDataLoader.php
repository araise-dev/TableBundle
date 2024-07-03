<?php

declare(strict_types=1);

namespace araise\TableBundle\DataLoader;

use araise\TableBundle\Extension\PaginationExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctrineDataLoader extends AbstractDataLoader
{
    public const OPT_QUERY_BUILDER = 'query_builder';

    public const OPT_SAVE_LAST_QUERY = 'save_last_query';

    protected const LAST_TABLE_QUERY = 'last_table_query';

    protected PaginationExtension $paginationExtension;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ?RequestStack $requestStack
    ) {
    }

    public function loadNecessaryExtensions(iterable $extensions): void
    {
        foreach ($extensions as $extension) {
            if ($extension instanceof PaginationExtension) {
                $this->paginationExtension = $extension;
                break;
            }
        }
    }

    public function getResults(): iterable
    {
        /** @var QueryBuilder $qb */
        $qb = (clone $this->options[self::OPT_QUERY_BUILDER]);
        $qb->select('COUNT('.$qb->getRootAliases()[0].')');

        if (count($qb->getDQLPart('groupBy')) > 0) {
            $sql = $qb->getQuery()->getSQL();
            $params = array_values(
                array_map(
                    static fn (Parameter $parameter) => $parameter->getValue(),
                    $qb->getParameters()->toArray()
                )
            );

            $sql = sprintf('SELECT COUNT(*) as row_count FROM (%s) AS sub;', $sql);
            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('row_count', 'row_count', 'integer');
            $count = $this->entityManager->createNativeQuery($sql, $rsm)->setParameters($params)->getSingleScalarResult();
            $this->paginationExtension->setTotalResults((int) $count);
        } else {
            $this->paginationExtension->setTotalResults($qb->getQuery()->getSingleScalarResult());
        }

        if ($this->getOption(self::OPT_SAVE_LAST_QUERY) && $this->requestStack->getCurrentRequest()?->hasSession()) {
            /** @var QueryBuilder $qbSave */
            $qbSave = (clone $this->options[self::OPT_QUERY_BUILDER]);
            $sql = $qbSave->select($qbSave->getRootAliases()[0].'.id')->getQuery()->getSql();
            $param = $qbSave->getParameters();
            $this->requestStack->getSession()->set(self::LAST_TABLE_QUERY, serialize([
                'sql' => $sql,
                'param' => $param,
                'entity' => $qbSave->getDQLPart('from')[0]->getFrom(),
            ]));
        } elseif ($this->requestStack->getCurrentRequest()?->hasSession()) {
            $this->requestStack->getSession()->remove(self::LAST_TABLE_QUERY);
        }

        if ($this->paginationExtension->getLimit()) {
            $this->options[self::OPT_QUERY_BUILDER]
                ->setMaxResults($this->paginationExtension->getLimit())
                ->setFirstResult($this->paginationExtension->getOffsetResults());
        }

        $paginator = new Paginator(
            $this->options[self::OPT_QUERY_BUILDER]
        );

        return $paginator->getIterator();
    }

    public function getNext(mixed $current): mixed
    {
        $sessionNext = $this->getSessionNextPrevData($current);

        if ($sessionNext === null) {
            return null;
        }
        $id = $this->getIdOf($current);
        $next = false;
        foreach ($sessionNext as $item) {
            if ($next) {
                return $this->entityManager->getRepository($current::class)->find($item['id']);
            }
            if ($item['id'] === $id) {
                $next = true;
            }
        }
        return null;
    }

    public function getPrev(mixed $current): mixed
    {
        $sessionNext = $this->getSessionNextPrevData($current);

        if ($sessionNext === null) {
            return null;
        }
        $id = $this->getIdOf($current);
        $last = null;
        foreach ($sessionNext as $item) {
            if ($item['id'] === $id) {
                return $last === null ? null : $this->entityManager->getRepository($current::class)->find($last['id']);
            }
            $last = $item;
        }
        return null;
    }

    public function getIdOf(mixed $current): mixed
    {
        try {
            return $current->getId();
        } catch (\Exception|\Error $e) {
            return null;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(self::OPT_QUERY_BUILDER);
        $resolver->setRequired(self::OPT_SAVE_LAST_QUERY);
        $resolver->setDefault(self::OPT_SAVE_LAST_QUERY, false);
        $resolver->setAllowedTypes(self::OPT_QUERY_BUILDER, QueryBuilder::class);
    }

    protected function getSessionNextPrevData(mixed $current): ?array
    {
        if (!$this->requestStack->getCurrentRequest()?->hasSession()) {
            return null;
        }

        $lastQuery = $this->requestStack?->getSession()->get(self::LAST_TABLE_QUERY);
        if ($lastQuery === null) {
            return null;
        }
        $lastQuery = unserialize($lastQuery);

        if ($current::class !== $lastQuery['entity']) {
            return null;
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id_0', 'id');
        $query = $this->entityManager->createNativeQuery($lastQuery['sql'], $rsm);
        foreach ($lastQuery['param'] as $i => $value) {
            $query->setParameter($i + 1, $value->getValue(), $value->getType());
        }
        return $query->getResult();
    }
}
