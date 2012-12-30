<?php

namespace AI;

class ResolveException extends \ErrorException
{
    public function __construct($varName, $className, \Reflector $method)
    {
        $message = 'Cannot resolve \'';
        if($className) $message.=$className.' ';
        $message.="\$$varName' for $method";
        parent::__construct($message);
    }
}
