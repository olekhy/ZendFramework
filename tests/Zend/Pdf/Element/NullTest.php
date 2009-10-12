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
 * @package    Zend_Pdf
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Zend_Pdf_Element_Null
 */
require_once 'Zend/Pdf/Element/Null.php';

/**
 * PHPUnit Test Case
 */
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * @category   Zend
 * @package    Zend_Pdf
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Pdf
 */
class Zend_Pdf_Element_NullTest extends PHPUnit_Framework_TestCase
{
    public function testPDFNull()
    {
        $nullObj = new Zend_Pdf_Element_Null();
        $this->assertTrue($nullObj instanceof Zend_Pdf_Element_Null);
    }

    public function testGetType()
    {
        $nullObj = new Zend_Pdf_Element_Null();
        $this->assertEquals($nullObj->getType(), Zend_Pdf_Element::TYPE_NULL);
    }

    public function testToString()
    {
        $nullObj = new Zend_Pdf_Element_Null();
        $this->assertEquals($nullObj->toString(), 'null');
    }
}
