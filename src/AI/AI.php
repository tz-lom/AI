<?php

namespace AI;

class AI
{
    protected $classesPool = array();
    protected $namesPool = array();

    public function registerValue($value, $className=NULL, $varName=NULL)
    {
        $this->register(function() use ($value) { return $value;}, $className, $varName);
    }

    public function registerClass($className, $varName=NULL)
    {
        $this->register(function($di) use ($className) { return $di->newInstance($className);}, $className, $varName);
    }

    public function registerSingleton($className, $varName=NULL)
    {
        $this->register(function($di) use ($className){
            static $instance=NULL;
            if(!$instance)
            {
                $instance = $di->newInstance($className);
            }
            return $instance;
        }, $className, $varName);
    }

    public function register(callable $factory, $className=NULL, $varName=NULL)
    {
        if($className == NULL && $varName == NULL) throw new \BadMethodCallException("Neither class nor variable name is defined");
        if($className==NULL)
        {
            $this->namesPool[$varName] = $factory;
        }
        else
        {
            $this->classesPool[$className][$varName] = $factory;

            $reflection = new \ReflectionClass($className);
            foreach($reflection->getInterfaceNames() as $interface)
            {
                if(isset($this->classesPool[$interface][$varName]))
                {
                    $this->classesPool[$interface][$varName] = NULL;
                }
                else
                {
                    $this->classesPool[$interface][$varName] = $this->classesPool[$className][$varName];
                }
            }
            $parent = $reflection;
            while(NULL != $parent = $parent->getParentClass())
            {
                if(!isset($this->classesPool[$parent->getName()][$varName]))
                {
                    $this->classesPool[$parent->getName()][$varName] = $this->classesPool[$className][$varName];
                }
            }
        }
    }

    public function newInstance($className, $arguments=array())
    {
        if(method_exists($className, '__construct'))
        {
            $instance = $this->call(array($className, '__construct'), $arguments);
        }
        else
        {
            if(class_exists($className))
            {
            $instance = new $className;
            }
            else
            {
                throw new InstantiateException($className);
            }
        }
        return $instance;
    }

    /**
     * @param string $className
     * @param string $varName
     * @return callable
     */
    protected function fabricFromPool($className, $varName)
    {
        // first try hint by class name
        if(isset($this->classesPool[$className]))
        {
            $pool = $this->classesPool[$className];
            if(isset($pool[$varName]))
            {
                return $pool[$varName];
            }
            if(isset($pool['']))
            {
                return $pool[''];
            }
        }
        if(isset($this->namesPool[$varName]))
        {
            return $this->namesPool[$varName];
        }
        return NULL;

    }

    public function call($callback, $arguments=array())
    {
        if(is_array($callback))
        {
            $reflector = new \ReflectionMethod($callback[0], $callback[1]);
        }
        elseif(is_object($callback) && !$callback instanceof \Closure)
        {
            $objReflector = new \ReflectionObject($callback);
            $reflector    = $objReflector->getMethod('__invoke');
        }
        else
        {
            $reflector = new \ReflectionFunction($callback);
        }

        $parameters = $reflector->getParameters();

        $params = [];

        foreach($parameters as &$param)
        {
            if($param->isOptional())
            {
                continue;
            }

            // if value specified - transfer it
            if(isset($arguments[$param->getName()]))
            {
                $params[] = $arguments[$param->getName()];
                continue;
            }

            $class = $param->getClass();
            if($class != NULL)
            {
                $class = $class->getName();
            }

            if(NULL != $constructor = $this->fabricFromPool($class, $param->getName()) )
            {
                $params[] = $constructor($this);
                continue;
            }

            if($class != NULL)
            {
                try {
                    $params[] = $this->newInstance($class, $arguments);
                    continue;
                } catch (InstantiateException $e) {
                    // suppress exception
                }
            }
            throw new ResolveException($param->getName(), $class, $reflector);

        }

        if(is_array($callback) && $callback[1] == '__construct')
        {
            return $reflector->getDeclaringClass()->newInstanceArgs($params);
        }
        else
        {
            return call_user_func_array($callback, $params);
        }
    }
}