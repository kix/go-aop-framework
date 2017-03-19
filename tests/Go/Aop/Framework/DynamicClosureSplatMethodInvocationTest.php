<?php
declare(strict_types = 1);

namespace Go\Aop\Framework;

use Go\Aop\Intercept\Interceptor;
use Go\Aop\Intercept\MethodInvocation;
use Go\Stubs\First;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-12-20 at 11:58:54.
 */
class DynamicClosureSplatMethodInvocationTest extends \PHPUnit_Framework_TestCase
{

    const FIRST_CLASS_NAME = First::class;

    protected static $invocationClass = DynamicClosureMethodInvocation::class;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (PHP_VERSION_ID < 50600) {
            $this->markTestSkipped("Closure Method Invocation with splat works only on PHP 5.6 and greater");
        }
    }

    /**
     * Tests dynamic method invocations
     *
     * @dataProvider dynamicMethodsBatch
     */
    public function testDynamicMethodInvocation($methodName, $expectedResult)
    {
        $child      = $this->createMock(self::FIRST_CLASS_NAME);
        $invocation = new self::$invocationClass(self::FIRST_CLASS_NAME, $methodName, []);

        $result = $invocation($child);
        $this->assertEquals($expectedResult, $result);
    }

    public function testValueChangedByReference()
    {
        $child      = $this->createMock(self::FIRST_CLASS_NAME);
        $invocation = new self::$invocationClass(self::FIRST_CLASS_NAME, 'passByReference', []);

        $value  = 'test';
        $result = $invocation($child, [&$value]);
        $this->assertEquals(null, $result);
        $this->assertEquals(null, $value);
    }

    public function testInvocationWithDynamicArguments()
    {
        $child      = $this->createMock(self::FIRST_CLASS_NAME);
        $invocation = new self::$invocationClass(self::FIRST_CLASS_NAME, 'variableArgsTest', []);

        $args     = [];
        $expected = '';
        for ($i=0; $i<10; $i++) {
            $args[]   = $i;
            $expected .= $i;
            $result   = $invocation($child, $args);
            $this->assertEquals($expected, $result);
        }
    }

    public function testInvocationWithVariadicArguments()
    {
        $child      = $this->createMock(self::FIRST_CLASS_NAME);
        $invocation = new self::$invocationClass(self::FIRST_CLASS_NAME, 'variadicArgsTest', []);

        $args     = [];
        $expected = '';
        for ($i=0; $i<10; $i++) {
            $args[]   = $i;
            $expected .= $i;
            $result   = $invocation($child, $args);
            $this->assertEquals($expected, $result);
        }
    }

    public function testRecursionWorks()
    {
        $child      = $this->createMock(self::FIRST_CLASS_NAME);
        $invocation = new self::$invocationClass(self::FIRST_CLASS_NAME, 'recursion', []);

        $child->expects($this->exactly(5))->method('recursion')->will($this->returnCallback(
            function ($value, $level) use ($child, $invocation) {
                return $invocation($child, [$value, $level]);
            }
        ));

        $this->assertEquals(5, $child->recursion(5,0));
        $this->assertEquals(20, $child->recursion(5,3));
    }

    public function testInterceptorIsCalledForInvocation()
    {
        $child  = $this->createMock(self::FIRST_CLASS_NAME);
        $value  = 'test';
        $advice = $this->createMock(Interceptor::class);
        $advice->expects($this->once())
            ->method('invoke')
            ->will($this->returnCallback(function (MethodInvocation $object) use (&$value) {
                $value = 'ok';
                return $object->proceed();
            }));

        $invocation = new self::$invocationClass(self::FIRST_CLASS_NAME, 'publicMethod', [$advice]);

        $result = $invocation($child, []);
        $this->assertEquals('ok', $value);
        $this->assertEquals(T_PUBLIC, $result);
    }

    public function dynamicMethodsBatch()
    {
        return [
            ['publicMethod', T_PUBLIC],
            ['protectedMethod', T_PROTECTED],
            // array('privateMethod', T_PRIVATE), This will throw an ReflectionException, need to add use case for that
        ];
    }
}
