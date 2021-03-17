<?php

namespace OK\Dto\Exception;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 */
class EntityManagerNotExistsException extends \Exception
{
    protected $message = 'You need to pass Doctrine Entity manager to constructor for using custom types';
}
