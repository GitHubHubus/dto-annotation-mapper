<?php

namespace Tests\Repository;

use OK\Dto\Repository\SearchCollectionInterface;
use OK\Dto\Repository\EntityRepository;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 */
class ExtendedEntityRepository extends EntityRepository implements SearchCollectionInterface
{
    public function findByIds(array $ids = []): array
    {
        return [];
    }
}
