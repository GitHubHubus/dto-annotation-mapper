<?php

namespace OK\Dto\Repository;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 */
interface SearchCollectionInterface
{
    public function findByIds(array $ids = []): array;
}
