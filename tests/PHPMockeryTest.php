<?php

namespace phpmock\mockery;

use phpmock\AbstractMockTest;
use Mockery;
use Mockery\MockInterface;

/**
 * Tests PHPMockery.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @see PHPMockery
 */
class PHPMockeryTest extends AbstractMockTest
{

    protected function disableMocks()
    {
        Mockery::close();
    }
    
    protected function defineFunction($namespace, $functionName)
    {
        PHPMockery::define($namespace, $functionName);
    }

    protected function mockFunction($namespace, $functionName, callable $function)
    {
        PHPMockery::mock($namespace, $functionName)->andReturnUsing($function);
    }
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->workaroundMockeryIssue268();
    }
    
    /**
     * Tests passing by reference as described in Mockery's manual with Mockery::on().
     *
     * @test
     * @link http://docs.mockery.io/en/latest/reference/pass_by_reference_behaviours.html
     */
    public function testMockeryPassByReference()
    {
        PHPMockery::mock(__NAMESPACE__, "exec")->with(
            "command",
            \Mockery::on(function (&$output) {
                $output = "output";
                return true;
            }),
            \Mockery::on(function (&$return_var) {
                $return_var = "return_var";
                return true;
            })
        )->once();
            
        exec("command", $output, $return_var);
        
        $this->assertEquals("output", $output);
        $this->assertEquals("return_var", $return_var);
    }
    
    /**
     * Workaround for Mockery's issue 268.
     *
     * Mockery-0.9 introduced global memoization of reflection methods. This
     * workaround clears that memoization to fix the affected tests.
     *
     * @link https://github.com/padraic/mockery/issues/268 Issue 268
     */
    private function workaroundMockeryIssue268()
    {
        foreach (get_declared_classes() as $class) {
            if (!is_subclass_of($class, MockInterface::class)) {
                continue;
            }
            try {
                $_mockery_methods = new \ReflectionProperty($class, "_mockery_methods");
                $_mockery_methods->setAccessible(true);
                $_mockery_methods->setValue(null);
            } catch (\ReflectionException $e) {
                // The unaffected version mockery-0.8 didn't had that property.
            }
        }
    }
}
