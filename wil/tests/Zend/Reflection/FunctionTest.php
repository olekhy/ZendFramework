<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Reflection
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see TestHelper
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';

/**
 * @see Zend_Reflection_Function
 */
require_once 'Zend/Reflection/Function.php';

/**
 * @category   Zend
 * @package    Zend_Reflection
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * 
 * @group Zend_Reflection
 * @group Zend_Reflection_Function
 */
class Zend_Reflection_FunctionTest extends PHPUnit_Framework_TestCase
{
    
    static protected $_sampleClassFileRequired = false;
    
    public function setup()
    {
        if (self::$_sampleClassFileRequired === false) {
            $fileToRequire = dirname(__FILE__) . '/_files/TestSampleClass.php';
            require_once $fileToRequire;
            self::$_sampleClassFileRequired = true;
        }
    }
    
    public function testConstructor() {
        $factory = new Zend_Reflection_Factory();
        $reflection1 = $factory->createFunction('array_splice');
        $this->assertType('Zend_Reflection_Function', $reflection1);

        $reflection2 = new Zend_Reflection_Function('array_splice');
        $this->assertType('Zend_Reflection_Function', $reflection2);
        
        // Make sure both instantiation methods return the same thing
        $this->assertEquals($reflection1, $reflection2);
    }
    
    public function testParemeterReturn()
    {
        $factory = new Zend_Reflection_Factory();
        $function = $factory->createFunction('array_splice');
        $parameters = $function->getParameters();
        $this->assertEquals(count($parameters), 4);
        $this->assertEquals(get_class(array_shift($parameters)), 'Zend_Reflection_Parameter');
    }
    
    public function testFunctionDocblockReturn()
    {
        $factory = new Zend_Reflection_Factory();
        $function = $factory->createFunction('zend_reflection_test_sample_function6');
        $this->assertEquals(get_class($function->getDocblock()), 'Zend_Reflection_Docblock');
    }
    
}
