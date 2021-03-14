<?php

namespace OK\Dto\Exception;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 */
class MethodNotImplementedException extends \Exception
{
    protected $message = 'You need to implement SearchCollectionInterface for entity repository to use ManyToMany relation';
}
