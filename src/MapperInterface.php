<?php

namespace App\Helper\DTO;

interface MapperInterface
{
    /**
     * @param mixed $object
     * @param array $data
     *
     * @return mixed
     */
    public function fillObject($object, $data);
}
