<?php

namespace OK\Dto\Repository;

use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;

class EntityRepository extends DoctrineEntityRepository implements SearchCollectionInterface
{
    public function findByIds(array $ids = []): array
    {
        $ids = array_filter($ids, function($el){
            return (int)$el > 0;
        });

        if (empty($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('a');

        return $qb->where($qb->expr()->in("a.id", $ids))->getQuery()->getResult();
    }
}
