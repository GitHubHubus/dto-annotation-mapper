<?php

namespace OK\Dto\Repository;

interface SearchCollectionInterface
{
    public function findByIds(array $ids = []): array;
}
