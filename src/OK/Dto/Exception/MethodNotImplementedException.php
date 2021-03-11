<?php

namespace OK\Dto\Exception;

class MethodNotImplementedException extends \Exception
{
    protected $message = 'You need to implement SearchCollectionInterface for entity repository to use ManyToMany relation';
}
