<?php

namespace OK\Dto;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 */
interface MapperInterface
{
    /**
     * @param mixed $object
     * @param array $data
     *
     * @return mixed
     */
    public function fillObject($object, array $data);
}
