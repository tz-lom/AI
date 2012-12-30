<?php

use AI\AI;

function ehlo($in)
{
    return $in;
}

function ehlo2($in1, $in2)
{
    return $in1.$in2;
}

function ehloFoo(Foo $f)
{
    return $f->data;
}

class Foo
{
    public $data;

    public function __construct($d='foo')
    {
        $this->data = $d;
    }
}

class FunctionCallTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleCall()
    {
        $di = new AI;
        $this->assertEquals('foo', $di->call('ehlo', array('in'=>'foo')));
        $this->assertEquals('foobar', $di->call('ehlo2', array('in1'=>'foo', 'in2'=>'bar')));
    }

    public function testSimpleAutocompletion()
    {
        $di = new AI;
        $di->registerClass('Foo');
        $this->assertEquals('foo', $di->call('ehloFoo'));
    }

    public function testAutocompletionOverride()
    {
        $di = new AI;
        $di->registerClass('Foo');
        $this->assertEquals(
            'bar',
            $di->call(
                'ehloFoo',
                ['f'=> new Foo('bar')]
            )
        );

        $di->registerValue(
            new Foo('bar'),
            NULL,
            'f'
        );
        $this->assertEquals(
            'foo',
            $di->call('ehloFoo')
        );

        $di->registerValue(
            new Foo('baz'),
            'Foo'
        );
        $this->assertEquals(
            'baz',
            $di->call('ehloFoo')
        );

    }

    public function testUnusedParameters()
    {
        $di = new AI;
        $this->assertEquals(
            'bar',
            $di->call(
                'ehlo',
                array(
                     'in'   => 'bar',
                     'foo'  => 'foo'
                )
            )
        );
    }

    public function testAutocompletionByName()
    {
        $di = new AI;
        $di->registerValue('baz', NULL, 'in');
        $this->assertEquals(
            'baz',
            $di->call('ehlo')
        );
        $this->assertEquals(
            'bar',
            $di->call(
                'ehlo',
                ['in' => 'bar']
            )
        );
    }
}
