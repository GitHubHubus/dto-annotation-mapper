<?php

namespace OK\Dto\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class DTO
{
    /** @internal <p>int, float, string, array, bool, datetime</p> */
    public ?string $type = null;

    public ?string $property = null;

    public ?string $name = null;

    public ?string $relation = null;
}
