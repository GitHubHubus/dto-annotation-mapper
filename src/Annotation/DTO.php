<?php

namespace App\Helper\DTO\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class DTO
{
    /** @var string <p>int, float, string, array, bool, datetime</p> */
    public $type = null;

    /** @var string */
    public $name = null;

    /** @var string */
    public $relation = null;
}
