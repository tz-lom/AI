<?php

namespace AI;

class InstantiateException extends \ErrorException
{
    public function __construct($name)
    {
        parent::__construct("'$name' is not a class");
    }
}