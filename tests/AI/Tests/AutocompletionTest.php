<?php

use AI\AI;

interface I {}

class A implements I
{
    public $data = 'A';
}

class B extends A
{
    public $data = 'B';
}

class C extends B
{
    public $data = 'C';
}

class AutocompletionTest extends \PHPUnit_Framework_TestCase
{

    public function testInheritance()
    {
        $di = new AI;
        $this->assertEquals(
            'A',
            $di->call(
                function(A $a) {
                    return $a->data;
                }
            )
        );

        $this->assertEquals(
            'B',
            $di->call(
                function(A $a){
                    return $a->data;
                },
                ['a' => new B]

            )
        );
    }

    /**
     * @expectedException \AI\ResolveException
     */
    public function testFailsCompleteInterface()
    {
        $di = new AI;
        $di->call(function(I $i){});
    }

    public function testImplements()
    {
        $di = new AI;
        $di->registerClass('B');
        $this->assertEquals(
            'B',
            $di->call(
                function(I $i){
                    return $i->data;
                }
            )
        );
    }

    /**
     * @expectedException \AI\ResolveException
     */
    public function testOverlappingInterfaceDefinition()
    {
        $ehlo = function(I $o){
            return $o->data;
        };

        $di = new AI;
        $di->registerClass('B');
        $this->assertEquals('B', $di->call($ehlo));

        $di->registerClass('C');
        $di->call($ehlo);
    }

    public function testExplicitInterfaceDefinition()
    {
        $di = new AI;
        $di->registerClass('C');
        $di->registerValue(new B(), 'I');

        $this->assertEquals(
            'B',
            $di->call(
                function(I $o){
                    return $o->data;
                }
            )
        );
    }

    public function testCommutativeDefinition()
    {
        $ehlo = function(B $o){
            return $o->data;
        };

        $di = new AI;
        $di->registerClass('C');
        $di->registerClass('B');
        $this->assertEquals('B', $di->call($ehlo));


        $di = new AI;
        $di->registerClass('B');
        $di->registerClass('C');
        $this->assertEquals('B', $di->call($ehlo));
    }
}