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
 * @package    Zend_Validator
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace ZendTest\Validator\Db;

use ReflectionClass;
use Zend\Db\ResultSet\Row;
use Zend\Validator\Db\RecordExists;

/**
 * @category   Zend
 * @package    Zend_Validator
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Validator
 */
class RecordExistsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Return a Mock object for a Db result with rows
     *
     * @return \Zend\Db\Adapter\Adapter
     */
    protected function getMockHasResult()
    {
        // mock the adapter, driver, and parts
        $mockConnection = $this->getMock('Zend\Db\Adapter\Driver\ConnectionInterface');

        // Mock has result
        $mockHasResultRow      = new Row();
        $mockHasResultRow->one = 'one';

        $mockHasResult = $this->getMock('Zend\Db\Adapter\Driver\ResultInterface');
        $mockHasResult->expects($this->any())
                      ->method('current')
                      ->will($this->returnValue($mockHasResultRow));

        $mockHasResultStatement = $this->getMock('Zend\Db\Adapter\Driver\StatementInterface');
        $mockHasResultStatement->expects($this->any())
                               ->method('execute')
                               ->will($this->returnValue($mockHasResult));

        $mockHasResultDriver = $this->getMock('Zend\Db\Adapter\Driver\DriverInterface');
        $mockHasResultDriver->expects($this->any())
                            ->method('createStatement')
                            ->will($this->returnValue($mockHasResultStatement));
        $mockHasResultDriver->expects($this->any())
                            ->method('getConnection')
                            ->will($this->returnValue($mockConnection));

        return $this->getMock('Zend\Db\Adapter\Adapter', null, array($mockHasResultDriver));
    }

    /**
     * Return a Mock object for a Db result without rows
     *
     * @return \Zend\Db\Adapter\Adapter
     */
    protected function getMockNoResult()
    {
        // mock the adapter, driver, and parts
        $mockConnection = $this->getMock('Zend\Db\Adapter\Driver\ConnectionInterface');

        $mockNoResult = $this->getMock('Zend\Db\Adapter\Driver\ResultInterface');
        $mockNoResult->expects($this->any())
                     ->method('current')
                     ->will($this->returnValue(null));

        $mockNoResultStatement = $this->getMock('Zend\Db\Adapter\Driver\StatementInterface');
        $mockNoResultStatement->expects($this->any())
                              ->method('execute')
                              ->will($this->returnValue($mockNoResult));

        $mockNoResultDriver = $this->getMock('Zend\Db\Adapter\Driver\DriverInterface');
        $mockNoResultDriver->expects($this->any())
                           ->method('createStatement')
                           ->will($this->returnValue($mockNoResultStatement));
        $mockNoResultDriver->expects($this->any())
                           ->method('getConnection')
                           ->will($this->returnValue($mockConnection));

        return $this->getMock('Zend\Db\Adapter\Adapter', null, array($mockNoResultDriver));
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @return void
     */
    public function testBasicFindsRecord()
    {
        $validator = new RecordExists('users', 'field1', $this->getMockHasResult());
        $this->assertTrue($validator->isValid('value1'));
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @return void
     */
    public function testBasicFindsNoRecord()
    {
        $validator = new RecordExists('users', 'field1', $this->getMockNoResult());
        $this->assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * Test the exclusion function
     *
     * @return void
     */
    public function testExcludeWithArray()
    {
        $validator = new RecordExists('users', 'field1', $this->getMockHasResult(),
                                                            array('field' => 'id',
                                                                    'value' => 1));
        $this->assertTrue($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     * with an array
     *
     * @return void
     */
    public function testExcludeWithArrayNoRecord()
    {
        $validator = new RecordExists('users', 'field1', $this->getMockNoResult(),
                                                            array('field' => 'id',
                                                                    'value' => 1));
        $this->assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * Test the exclusion function
     * with a string
     *
     * @return void
     */
    public function testExcludeWithString()
    {
        $validator = new RecordExists('users', 'field1', $this->getMockHasResult(), 'id != 1');
        $this->assertTrue($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     * with a string
     *
     * @return void
     */
    public function testExcludeWithStringNoRecord()
    {
        $validator = new RecordExists('users', 'field1', $this->getMockNoResult(), 'id != 1');
        $this->assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * Test that the class throws an exception if no adapter is provided
     * and no default is set.
     *
     * @return void
     */
    public function testThrowsExceptionWithNoAdapter()
    {
        $this->setExpectedException('Zend\Validator\Exception\InvalidArgumentException',
                                    'No database adapter present!');
        $validator = new RecordExists('users', 'field1', null, 'id != 1');
        $validator->isValid('nosuchvalue');
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @return void
     */
    public function testWithSchema()
    {
        $validator = new RecordExists(array('table'   => 'users',
                                            'schema'  => 'my',
                                            'field'   => 'field1',
                                            'adapter' => $this->getMockHasResult()));
        $this->assertTrue($validator->isValid('value1'));
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @return void
     */
    public function testWithSchemaNoResult()
    {
        $validator = new RecordExists(array('table'   => 'users',
                                            'schema'  => 'my',
                                            'field'   => 'field1',
                                            'adapter' => $this->getMockNoResult()));
        $this->assertFalse($validator->isValid('value1'));
    }

    public function testEqualsMessageTemplates()
    {
        $validator  = new RecordExists('users', 'field1', $this->getMockNoResult());
        $reflection = new ReflectionClass($validator);

        if (!$reflection->hasProperty('_messageTemplates')) {
            return;
        }

        $property = $reflection->getProperty('_messageTemplates');
        $property->setAccessible(true);

        $this->assertEquals(
            $property->getValue($validator),
            $validator->getOption('messageTemplates')
        );
    }

    public function testEqualsMessageVariables()
    {
        $validator  = new RecordExists('users', 'field1', $this->getMockNoResult());
        $reflection = new ReflectionClass($validator);

        if (!$reflection->hasProperty('_messageVariables')) {
            return;
        }

        $property = $reflection->getProperty('_messageVariables');
        $property->setAccessible(true);

        $this->assertEquals(
            $property->getValue($validator),
            $validator->getOption('messageVariables')
        );
    }
}
