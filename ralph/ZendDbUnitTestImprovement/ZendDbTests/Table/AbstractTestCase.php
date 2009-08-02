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
 * @package    Zend_Db
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @see Zend_Db_Table_TestSetup
 */
require_once 'Zend/Db/TestSuite/AbstractTestCase.php';

/**
 * @see Zend_Registry
 */
require_once 'Zend/Registry.php';

require_once 'Zend/Db/Table/Abstract.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__);


/**
 * @category   Zend
 * @package    Zend_Db
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Db_Table_AbstractTestCase extends Zend_Db_TestSuite_AbstractTestCase
{
    
    public function setUp()
    {
        parent::setUp();
        if (!$this->sharedFixture->tableUtility->isOriginalIncludePath()) {
            $this->sharedFixture->tableUtility->restoreIncludePath();
        }
    }
    
    public function tearDown()
    {
        parent::tearDown();
        if (!$this->sharedFixture->tableUtility->isOriginalIncludePath()) {
            $this->sharedFixture->tableUtility->restoreIncludePath();
        }
    }
    
    /**
     * _getTableById()
     *
     * @param string $tableId
     * @return Zend_Db_Table_Abstract
     */
    protected function _getTableById($tableId, $options = array())
    {
        return $this->sharedFixture->tableUtility->getTableById($tableId, $options);
    }
    
    
    public function testTableConstructor()
    {
        $bugs = $this->_getTableById('Bugs');
        $info = $bugs->info();

        $config = array(
            'db'              => $this->sharedFixture->dbAdapter,
            'schema'          => $info['schema'],
            'name'            => $info['name'],
            'primary'         => $info['primary'],
            'cols'            => $info['cols'],
            'metadata'        => $info['metadata'],
            'metadataCache'   => null,
            'rowClass'        => $info['rowClass'],
            'rowsetClass'     => $info['rowsetClass'],
            'referenceMap'    => $info['referenceMap'],
            'dependentTables' => $info['dependentTables'],
            'sequence'        => $info['sequence'],
            'unknownKey'      => 'testValue'
            );

        $table = new My_ZendDbTable_TableBugs($config);
    }

    // ZF-2379
    public function testAddReference()
    {
        $expectedReferences = array(
            'columns'           => array('reported_by'),
            'refTableClass'     => 'My_ZendDbTable_TableAccounts',
            'refColumns'        => array('account_name')
        );

        $products = $this->_getTableById('Products');
        $products->addReference('Reporter', 'reported_by',
                                'My_ZendDbTable_TableAccounts', 'account_name');

        $references = $products->getReference('My_ZendDbTable_TableAccounts');

        $this->assertEquals($expectedReferences, $references);
    }

    // ZF-2666
    public function testIsIdentity()
    {
        $bugs = $this->_getTableById('Bugs');

        $this->assertTrue($bugs->isIdentity('bug_id'));
    }

    /**
     * @group ZF-2510
     */
    public function testMetadataCacheInClassFlagShouldBeEnabledByDefault()
    {
        $bugs = $this->_getTableById('Bugs');
        $this->assertTrue($bugs->metadataCacheInClass());
    }

    /**
     * @group ZF-2510
     */
    public function testMetadataCacheInClassFlagShouldBeMutable()
    {
        $bugs = $this->_getTableById('Bugs');
        $this->assertTrue($bugs->metadataCacheInClass());
        $bugs->setMetadataCacheInClass(false);
        $this->assertFalse($bugs->metadataCacheInClass());
    }

    public function testTableInfo()
    {
        $bugs = $this->_getTableById('Bugs');
        $this->assertType('Zend_Db_Table_Abstract', $bugs);
        $info = $bugs->info();

        $keys = array(
            Zend_Db_Table_Abstract::SCHEMA,
            Zend_Db_Table_Abstract::NAME,
            Zend_Db_Table_Abstract::COLS,
            Zend_Db_Table_Abstract::PRIMARY,
            Zend_Db_Table_Abstract::METADATA,
            Zend_Db_Table_Abstract::ROW_CLASS,
            Zend_Db_Table_Abstract::ROWSET_CLASS,
            Zend_Db_Table_Abstract::REFERENCE_MAP,
            Zend_Db_Table_Abstract::DEPENDENT_TABLES,
            Zend_Db_Table_Abstract::SEQUENCE,
        );

        $this->assertEquals($keys, array_keys($info));

        $this->assertEquals('zf_bugs', $info['name']);

        $this->assertEquals(8, count($info['cols']));
        $cols = array(
            'bug_id',
            'bug_description',
            'bug_status',
            'created_on',
            'updated_on',
            'reported_by',
            'assigned_to',
            'verified_by'
        );
        $this->assertEquals($cols, $info['cols']);

        $this->assertEquals(1, count($info['primary']));
        $pk = array('bug_id');
        $this->assertEquals($pk, array_values($info['primary']));

        $name = $bugs->info(Zend_Db_Table_Abstract::NAME);
        $this->assertEquals('zf_bugs', $name);

        try {
            $value = $bugs->info('_non_existent_');
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e);
            $this->assertEquals('There is no table information for the key "_non_existent_"', $e->getMessage());
        }
    }

    /**
     * Ensures expected behavior when a table is assigned a Row class of stdClass
     *
     * @return void
     */
    public function testTableSetRowClassStdclass()
    {
        $productRowset = $this->_getTableById('Products')->setRowClass('stdClass')->fetchAll();

        $this->assertEquals(
            3,
            $productRowsetCount = count($productRowset),
            "Expected rowset with 3 elements; got $productRowsetCount"
            );

        foreach ($productRowset as $productRow) {
            $this->assertThat(
                $productRow,
                $this->isInstanceOf('stdClass'),
                'Expected row to be instance of stdClass; got ' . get_class($productRow)
                );
        }
    }

    /**
     * Ensures expected behavior when a table is assigned a Rowset class of stdClass
     *
     * @return void
     */
    public function testTableSetRowsetClassStdclass()
    {
        $productRowset = $this->_getTableById('Products')->setRowsetClass('stdClass')->fetchAll();

        $this->assertThat(
            $productRowset,
            $this->isInstanceOf('stdClass'),
            'Expected rowset to be instance of stdClass; got ' . get_class($productRowset)
            );
    }

    public function testTableImplicitName()
    {
        include_once '_files/My/ZendDbTable/TableSpecial.php';
        
        // TableSpecial.php contains class bugs_products too.
        $table = new zf_bugs_products(array('db' => $this->sharedFixture->dbAdapter));
        $info = $table->info();
        $this->assertContains('name', array_keys($info));
        $this->assertEquals('zf_bugs_products', $info['name']);
    }

    public function testTableOptionName()
    {
        $tableName = 'zf_bugs';
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableSpecial',
            array('name' => $tableName)
        );
        $info = $table->info();
        $this->assertContains('name', array_keys($info));
        $this->assertEquals($tableName, $info['name']);
    }

    public function testTableOptionSchema()
    {
        $schemaName = $this->sharedFixture->dbUtility->getSchema();
        $tableName = 'zf_bugs';
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableSpecial',
            array('name' => $tableName, 'schema' => $schemaName)
        );
        $info = $table->info();
        $this->assertContains('schema', array_keys($info));
        $this->assertEquals($schemaName, $info['schema']);
    }

    public function testTableArgumentAdapter()
    {
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs',
            $this->sharedFixture->dbAdapter);
        $db = $table->getAdapter();
        $this->assertSame($this->sharedFixture->dbAdapter, $db);
    }

    public function testTableOptionAdapter()
    {
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs',
            array('db' => $this->sharedFixture->dbAdapter));
        $db = $table->getAdapter();
        $this->assertSame($this->sharedFixture->dbAdapter, $db);
    }

    public function testTableOptionRowClass()
    {
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs',
            array('rowClass' => 'stdClass'));
        $rowClass = $table->getRowClass();
        $this->assertEquals($rowClass, 'stdClass');

        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs',
            array('rowsetClass' => 'stdClass'));
        $rowsetClass = $table->getRowsetClass();
        $this->assertEquals($rowsetClass, 'stdClass');
    }

    public function testTableGetRowClass()
    {
        $table = $this->_getTableById('Products');
        $this->assertType('Zend_Db_Table_Abstract', $table);

        $rowClass = $table->getRowClass();
        $this->assertEquals($rowClass, 'Zend_Db_Table_Row');

        $rowsetClass = $table->getRowsetClass();
        $this->assertEquals($rowsetClass, 'Zend_Db_Table_Rowset');
    }

    public function testTableOptionReferenceMap()
    {
        $refReporter = array(
            'columns'           => array('reported_by'),
            'refTableClass'     => 'My_ZendDbTable_TableAccounts',
            'refColumns'        => array('account_id')
        );
        $refEngineer = array(
            'columns'           => array('assigned_to'),
            'refTableClass'     => 'My_ZendDbTable_TableAccounts',
            'refColumns'        => array('account_id')
        );
        $refMap = array(
            'Reporter' => $refReporter,
            'Engineer' => $refEngineer
        );
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs',
            array('referenceMap' => $refMap));

        $this->assertEquals($refReporter, $table->getReference('My_ZendDbTable_TableAccounts'));
        $this->assertEquals($refReporter, $table->getReference('My_ZendDbTable_TableAccounts', 'Reporter'));
        $this->assertEquals($refEngineer, $table->getReference('My_ZendDbTable_TableAccounts', 'Engineer'));
    }

    public function testTableExceptionOptionReferenceMap()
    {
        $refReporter = array(
            'columns'           => array('reported_by'),
            'refTableClass'     => 'My_ZendDbTable_TableAccounts',
            'refColumns'        => array('account_id')
        );
        $refEngineer = array(
            'columns'           => array('assigned_to'),
            'refTableClass'     => 'My_ZendDbTable_TableAccounts',
            'refColumns'        => array('account_id')
        );
        $refMap = array(
            'Reporter' => $refReporter,
            'Engineer' => $refEngineer
        );
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs',
            array('referenceMap' => $refMap));

        try {
            $ref = $table->getReference('My_ZendDbTable_TableAccounts', 'Verifier');
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e);
            $this->assertEquals('No reference rule "Verifier" from table My_ZendDbTable_TableBugs to table My_ZendDbTable_TableAccounts', $e->getMessage());
        }

        try {
            $ref = $table->getReference('My_ZendDbTable_TableProducts');
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e);
            $this->assertEquals('No reference from table My_ZendDbTable_TableBugs to table My_ZendDbTable_TableProducts', $e->getMessage());
        }

        try {
            $ref = $table->getReference('My_ZendDbTable_TableProducts', 'Product');
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e);
            $this->assertEquals('No reference rule "Product" from table My_ZendDbTable_TableBugs to table My_ZendDbTable_TableProducts', $e->getMessage());
        }

        try {
            $ref = $table->getReference('My_ZendDbTable_TableProducts', 'Reporter');
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e);
            $this->assertEquals('Reference rule "Reporter" does not reference table My_ZendDbTable_TableProducts', $e->getMessage());
        }

    }

    public function testTableOptionDependentTables()
    {
        $depTables = array('Zend_Db_Table_Foo');
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs',
            array('dependentTables' => $depTables));
        $this->assertEquals($depTables, $table->getDependentTables());
    }

    public function testTableSetRowClass()
    {
        $table = $this->_getTableById('Products');
        $this->assertType('Zend_Db_Table_Abstract', $table);

        $table->setRowClass('stdClass');
        $rowClass = $table->getRowClass();
        $this->assertEquals($rowClass, 'stdClass');

        $table->setRowsetClass('stdClass');
        $rowsetClass = $table->getRowsetClass();
        $this->assertEquals($rowsetClass, 'stdClass');
    }

    public function testTableSetDefaultAdapter()
    {
        /**
         * Don't use _getTable() method because it defaults the adapter
         */
        Zend_Loader::loadClass('My_ZendDbTable_TableBugs');
        Zend_Db_Table_Abstract::setDefaultAdapter($this->sharedFixture->dbAdapter);
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $this->assertSame($this->sharedFixture->dbAdapter, $db);
        $table = new My_ZendDbTable_TableBugs();
        $db = $table->getAdapter();
        $this->assertSame($this->sharedFixture->dbAdapter, $db);
    }

    public function testTableWithNoAdapterAndNoDefaultAdapter()
    {
        Zend_Db_Table_Abstract::setDefaultAdapter(null);
        $this->assertNull(Zend_Db_Table_Abstract::getDefaultAdapter());
        try {
            $this->sharedFixture->tableUtility->useMyIncludePath();
            Zend_Loader::loadClass('My_ZendDbTable_TableBugs');
            $table = new My_ZendDbTable_TableBugs();
            $this->fail('Zend_Db_Table_Exception should be thrown');
        }catch(Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
        }
    }

    public function testTableSetDefaultAdapterNull()
    {
        Zend_Db_Table_Abstract::setDefaultAdapter($this->sharedFixture->dbAdapter);
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $this->assertSame($this->sharedFixture->dbAdapter, $db);
        Zend_Db_Table_Abstract::setDefaultAdapter();
        $this->assertNull(Zend_Db_Table_Abstract::getDefaultAdapter());
    }

    public function testTableSetDefaultAdapterRegistry()
    {
        /**
         * Don't use _getTable() method because it defaults the adapter
         */
        Zend_Loader::loadClass('My_ZendDbTable_TableBugs');
        Zend_Registry::set('registered_db', $this->sharedFixture->dbAdapter);
        Zend_Db_Table_Abstract::setDefaultAdapter('registered_db');
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $this->assertSame($this->sharedFixture->dbAdapter, $db);
        $table = new My_ZendDbTable_TableBugs();
        $db = $table->getAdapter();
        $this->assertSame($this->sharedFixture->dbAdapter, $db);
    }

    public function testTableSetDefaultAdapterException()
    {
        try {
            Zend_Db_Table_Abstract::setDefaultAdapter(new stdClass());
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals("Argument must be of type Zend_Db_Adapter_Abstract, or a Registry key where a Zend_Db_Adapter_Abstract object is stored", $e->getMessage());
        }

        try {
            Zend_Db_Table_Abstract::setDefaultAdapter(327);
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals("Argument must be of type Zend_Db_Adapter_Abstract, or a Registry key where a Zend_Db_Adapter_Abstract object is stored", $e->getMessage());
        }
    }

    public function testTableExceptionPrimaryKeyNotSpecified()
    {
        try {
            $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs', array('primary' => ''));
            $primary = $table->info(Zend_Db_Table_Abstract::PRIMARY);
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertContains("Primary key column(s)", $e->getMessage());
            $this->assertContains("are not columns in this table", $e->getMessage());
        }
    }

    public function testTableExceptionInvalidPrimaryKey()
    {
        try {
            $table   = new My_ZendDbTable_TableBugs(array('primary' => 'foo'));
            $primary = $table->info(Zend_Db_Table_Abstract::PRIMARY);
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertContains("Primary key column(s)", $e->getMessage());
            $this->assertContains("are not columns in this table", $e->getMessage());
        }
    }

    public function testTableExceptionNoPrimaryKey()
    {
        $clonedUtility = $this->_getClonedUtility(false);
        $tableName = $clonedUtility->createTable(
            'NoPrimaryKey',
            'no_primary_key',
            array('id' => 'INTEGER')
            );

        try {
            $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableSpecial',
                array('name' => $tableName));
            $primary = $table->info(Zend_Db_Table_Abstract::PRIMARY);
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals('A table must have a primary key, but none was found', $e->getMessage());
        }

    }

    public function testTableWithNoPrimaryKeyButOptionSpecifiesOne()
    {
        $clonedUtility = $this->_getClonedUtility(false);
        $tableName = $clonedUtility->createTable(
            'NoPrimaryKey',
            'no_primary_key',
            array('id' => 'INTEGER')
            );

        try {
            $table = $this->sharedFixture->tableUtility->getTable(
                'My_ZendDbTable_TableSpecial',
                array('name' => $tableName, 'primary' => 'id')
                );
        } catch (Zend_Exception $e) {
            $this->fail('Expected to succeed without a Zend_Db_Table_Exception');
        }

        $info = $table->info();
        $this->assertEquals(array(1=>'id'), $info['primary']);

    }

    public function testTableAdapterException()
    {
        Zend_Loader::loadClass('My_ZendDbTable_TableBugs');

        /**
         * options array points 'db' to integer scalar
         */
        try {
            $table = new My_ZendDbTable_TableBugs(array('db' => 327));
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals("Argument must be of type Zend_Db_Adapter_Abstract, or a Registry key where a Zend_Db_Adapter_Abstract object is stored", $e->getMessage());
        }

        /**
         * options array points 'db' to Registry key containing integer scalar
         */
        Zend_Registry::set('registered_db', 327);
        try {
            $table = new My_ZendDbTable_TableBugs(array('db' => 'registered_db'));
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals("Argument must be of type Zend_Db_Adapter_Abstract, or a Registry key where a Zend_Db_Adapter_Abstract object is stored", $e->getMessage());
        }
    }

    public function testTableFindSingleRow()
    {
        $table = $this->_getTableById('Bugs');
        $rowset = $table->find(1);
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(1, count($rowset));
    }

    public function testTableFindMultipleRows()
    {
        $table = $this->_getTableById('Bugs');
        $rowset = $table->find(array(1, 2));
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(2, count($rowset));
    }

    public function testTableFindExceptionTooFewKeys()
    {
        $table = $this->_getTableById('BugsProducts');
        try {
            $table->find(1);
            $this->fail('Expected to catch Zend_Db_Table_Exception for missing key');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals('Too few columns for the primary key', $e->getMessage());
        }
    }

    public function testTableFindExceptionTooManyKeys()
    {
        $table = $this->_getTableById('Bugs');
        try {
            $table->find(1, 2);
            $this->fail('Expected to catch Zend_Db_Table_Exception for incorrect key count');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals('Too many columns for the primary key', $e->getMessage());
        }
    }

    public function testTableFindCompoundSingleRow()
    {
        $table = $this->_getTableById('BugsProducts');
        $rowset = $table->find(1, 2);
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(1, count($rowset));
    }

    public function testTableFindCompoundMultipleRows()
    {
        $table = $this->_getTableById('BugsProducts');
        $rowset = $table->find(array(1, 1), array(2, 3));
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(2, count($rowset));
    }

    public function testTableFindCompoundMultipleExceptionIncorrectValueCount()
    {
        $table = $this->_getTableById('BugsProducts');
        try {
            $rowset = $table->find(array(1, 1), 2);
            $this->fail('Expected to catch Zend_Db_Table_Exception for incorrect key count');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals('Missing value(s) for the primary key', $e->getMessage());
        }
    }

    public function testTableInsert()
    {
        $table = $this->_getTableById('Bugs');
        $row = array (
            'bug_id'          => null,
            'bug_description' => 'New bug',
            'bug_status'      => 'NEW',
            'created_on'      => '2007-04-02',
            'updated_on'      => '2007-04-02',
            'reported_by'     => 'micky',
            'assigned_to'     => 'goofy',
            'verified_by'     => 'dduck'
        );
        $insertResult = $table->insert($row);
        $lastInsertId = $this->sharedFixture->dbAdapter->lastInsertId();
        $this->assertEquals($insertResult, $lastInsertId);
        $this->assertEquals(5, $lastInsertId);
    }

    public function testTableInsertWithSchema()
    {
        $schemaName = $this->sharedFixture->dbUtility->getSchema();
        $tableName = 'zf_bugs';
        $identifier = join('.', array_filter(array($schemaName, $tableName)));
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableSpecial',
            array('name' => $tableName, 'schema' => $schemaName)
        );

        $row = array (
            'bug_description' => 'New bug',
            'bug_status'      => 'NEW',
            'created_on'      => '2007-04-02',
            'updated_on'      => '2007-04-02',
            'reported_by'     => 'micky',
            'assigned_to'     => 'goofy',
            'verified_by'     => 'dduck'
        );

        $profilerEnabled = $this->sharedFixture->dbAdapter->getProfiler()->getEnabled();
        $this->sharedFixture->dbAdapter->getProfiler()->setEnabled(true);
        $insertResult = $table->insert($row);
        $this->sharedFixture->dbAdapter->getProfiler()->setEnabled($profilerEnabled);

        if ($table->info(Zend_Db_Table_Abstract::SEQUENCE) === true) {
            $qp = $this->sharedFixture->dbAdapter->getProfiler()->getQueryProfile(1);
        } else {
            $qp = $this->sharedFixture->dbAdapter->getProfiler()->getLastQueryProfile();
        }
        $tableSpec = $this->sharedFixture->dbAdapter->quoteIdentifier($identifier, true);
        $this->assertContains("INSERT INTO $tableSpec ", $qp->getQuery());
    }

    public function testTableInsertSequence()
    {
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableProducts',
            array(Zend_Db_Table_Abstract::SEQUENCE => 'zf_products_seq'));

        $row = array (
            'product_name' => 'Solaris'
        );
        $insertResult         = $table->insert($row);
        $lastInsertId         = $this->sharedFixture->dbAdapter->lastInsertId('zf_products');
        $lastSequenceId       = $this->sharedFixture->dbAdapter->lastSequenceId('zf_products_seq');
        $this->assertEquals($insertResult, $lastInsertId);
        $this->assertEquals($insertResult, $lastSequenceId);
        $this->assertEquals(4, $insertResult);
    }

    public function testTableInsertNaturalCompound()
    {
        $table = $this->_getTableById('BugsProducts');
        $row = array(
            'bug_id'     => 2,
            'product_id' => 1
        );
        $primary = $table->insert($row);
        $this->assertType('array', $primary);
        $this->assertEquals(2, count($primary));
        $this->assertEquals(array(2, 1), array_values($primary));
    }

    /**
     * @todo
     *
    public function testTableInsertNaturalExceptionKeyViolation()
    {
        $table = $this->_getTableById('Bugs');
        $row = array (
            'bug_id'          => 1,
            'bug_description' => 'New bug',
            'bug_status'      => 'NEW',
            'created_on'      => '2007-04-02',
            'updated_on'      => '2007-04-02',
            'reported_by'     => 'micky',
            'assigned_to'     => 'goofy'
        );
        try {
            $insertResult = $table->insert($row);
            $this->fail('Expected to catch Zend_Db_Table_Exception for key violation');
        } catch (Zend_Exception $e) {
            echo "*** caught ".get_class($e)."\n";
            echo "*** ".$e->getMessage()."\n";
            $this->assertEquals('xxx', $e->getMessage());
        }
    }
     */

    /**
     * @todo
     *
    public function testTableInsertNaturalCompoundExceptionKeyViolation()
    {
        $table = $this->_getTableById('BugsProducts');
        $row = array(
            'bug_id'     => 1,
            'product_id' => 1
        );
        try {
            $table->insert($row);
            $this->fail('Expected to catch Zend_Db_Table_Exception for key violation');
        } catch (Zend_Exception $e) {
            echo "*** caught ".get_class($e)."\n";
            echo "*** ".$e->getMessage()."\n";
            $this->assertEquals('xxx', $e->getMessage());
        }
    }
     */

    /**
     * See ZF-1739 in our issue tracker.
     */
    public function testTableInsertMemoryUsageZf1739()
    {
        $this->markTestSkipped('Very slow test inserts thousands of rows');

        $table = $this->_getTableById('Products');

        // insert one row to prime the pump
        $table->insert(array('product_name' => "product0"));

        // measure current memory usage
        $mem1 = memory_get_usage();

        // insert a lot of rows
        $n = 100000;
        for ($i = 1; $i <= $n; $i++)
        {
            $table->insert(array('product_name' => "product$i"));
            if ($i % 1000 == 0) {
                echo '.';
            }
        }

        // measure new memory usage
        $mem2 = memory_get_usage();

        // compare new memory usage to original
        $mem_delta = $mem2-$mem1;
        $this->assertThat($mem_delta, $this->lessThan(513));
    }

    public function testTableUpdate()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);
        $bug_description = $this->sharedFixture->dbAdapter->foldCase('bug_description');
        $bug_status      = $this->sharedFixture->dbAdapter->foldCase('bug_status');
        $data = array(
            $bug_description => 'Implement Do What I Mean function',
            $bug_status      => 'INCOMPLETE'
        );
        $table = $this->_getTableById('Bugs');
        $result = $table->update($data, "$bug_id = 2");
        $this->assertEquals(1, $result);

        // Query the row to see if we have the new values.
        $rowset = $table->find(2);
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(1, count($rowset), "Expecting rowset count to be 1");
        $row = $rowset->current();
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row->$bug_id, "Expecting row->bug_id to be 2");
        $this->assertEquals($data[$bug_description], $row->$bug_description);
        $this->assertEquals($data[$bug_status], $row->$bug_status);
    }

    public function testTableUpdateWithSchema()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);
        $bug_description = $this->sharedFixture->dbAdapter->foldCase('bug_description');
        $bug_status      = $this->sharedFixture->dbAdapter->foldCase('bug_status');
        $schemaName = $this->sharedFixture->dbUtility->getSchema();
        $tableName = 'zf_bugs';
        $identifier = join('.', array_filter(array($schemaName, $tableName)));
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableSpecial',
            array('name' => $tableName, 'schema' => $schemaName)
        );

        $data = array(
            $bug_description => 'Implement Do What I Mean function',
            $bug_status      => 'INCOMPLETE'
        );

        $profilerEnabled = $this->sharedFixture->dbAdapter->getProfiler()->getEnabled();
        $this->sharedFixture->dbAdapter->getProfiler()->setEnabled(true);
        $result = $table->update($data, "$bug_id = 2");
        $this->sharedFixture->dbAdapter->getProfiler()->setEnabled($profilerEnabled);

        $this->assertEquals(1, $result);
        $qp = $this->sharedFixture->dbAdapter->getProfiler()->getLastQueryProfile();
        $tableSpec = $this->sharedFixture->dbAdapter->quoteIdentifier($identifier, true);
        $this->assertContains("UPDATE $tableSpec ", $qp->getQuery());
    }

    public function testTableUpdateWhereArray()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);
        $bug_status = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_status', true);

        $bug_description = $this->sharedFixture->dbAdapter->foldCase('bug_description');
        $data = array(
            $bug_description => 'Synesthesia',
        );

        $where = array(
            "$bug_id IN (1, 3)",
            "$bug_status != 'UNKNOWN'"
            );

        $this->assertEquals(2, $this->_getTableById('Bugs')->update($data, $where));

        $count = 0;
        foreach ($this->_getTableById('Bugs')->find(array(1, 3)) as $row) {
            $this->assertEquals($data[$bug_description], $row->$bug_description);
            ++$count;
        }

        $this->assertEquals(2, $count);
    }

    public function testTableDelete()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');
        $rowset = $table->find(array(1, 2));
        $this->assertEquals(2, count($rowset));

        $table->delete("$bug_id = 2");

        $rowset = $table->find(array(1, 2));
        $this->assertEquals(1, count($rowset));
    }

    public function testTableDeleteWithSchema()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);
        $schemaName = $this->sharedFixture->dbUtility->getSchema();
        $tableName = 'zf_bugs';
        $identifier = join('.', array_filter(array($schemaName, $tableName)));
        $table = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableSpecial',
            array('name' => $tableName, 'schema' => $schemaName)
        );

        $profilerEnabled = $this->sharedFixture->dbAdapter->getProfiler()->getEnabled();
        $this->sharedFixture->dbAdapter->getProfiler()->setEnabled(true);
        $result = $table->delete("$bug_id = 2");
        $this->sharedFixture->dbAdapter->getProfiler()->setEnabled($profilerEnabled);

        $qp = $this->sharedFixture->dbAdapter->getProfiler()->getLastQueryProfile();
        $tableSpec = $this->sharedFixture->dbAdapter->quoteIdentifier($identifier, true);
        $this->assertContains("DELETE FROM $tableSpec ", $qp->getQuery());
    }

    public function testTableDeleteWhereArray()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);
        $bug_status = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_status', true);

        $where = array(
            "$bug_id IN (1, 3)",
            "$bug_status != 'UNKNOWN'"
            );

        $this->assertEquals(2, $this->_getTableById('Bugs')->delete($where));

        $this->assertEquals(0, count($this->_getTableById('Bugs')->find(array(1, 3))));
    }

    public function testTableCreateRow()
    {
        $table = $this->_getTableById('Bugs');
        $row = $table->createRow();
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $this->assertTrue(isset($row->bug_description));
        $this->assertEquals($row, $table->fetchNew());
    }

    public function testTableCreateRowWithData()
    {
        $table = $this->_getTableById('Bugs');
        $data = array (
            'bug_description' => 'New bug',
            'bug_status'      => 'NEW',
            'created_on'      => '2007-04-02',
            'updated_on'      => '2007-04-02',
            'reported_by'     => 'micky',
            'assigned_to'     => 'goofy'
        );
        $row = $table->createRow($data);
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $this->assertTrue(isset($row->bug_description));
        $this->assertEquals('New bug', $row->bug_description);
    }

    public function testTableFetchRow()
    {
        $table = $this->_getTableById('Bugs');
        $bug_description = $this->sharedFixture->dbAdapter->foldCase('bug_description');
        $row = $table->fetchRow();
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $this->assertTrue(isset($row->$bug_description));
    }

    public function testTableFetchRowWhere()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');
        $row = $table->fetchRow("$bug_id = 2");
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row->$bug_id);
    }

    public function testTableFetchRowWhereArray()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');
        $row = $table->fetchRow(array("$bug_id = ?" => 2));
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row->$bug_id);
    }

    public function testTableFetchRowWhereSelect()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');
        $select = $table->select()
            ->where("$bug_id = ?", 2);

        $row = $table->fetchRow($select);
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row->$bug_id);
    }

    public function testTableFetchRowOrderAsc()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');

        $row = $table->fetchRow("$bug_id > 1", "bug_id ASC");
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row->$bug_id);
    }

    public function testTableFetchRowOrderSelectAsc()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');
        $select = $table->select()
            ->where("$bug_id > ?", 1)
            ->order("bug_id ASC");

        $row = $table->fetchRow($select);
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row->$bug_id);
    }

    public function testTableFetchRowOrderDesc()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');

        $row = $table->fetchRow(null, "bug_id DESC");
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(4, $row->$bug_id);
    }

    public function testTableFetchRowOrderSelectDesc()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');
        $select = $table->select()
            ->where("$bug_id > ?", 1)
            ->order("bug_id DESC");

        $row = $table->fetchRow($select);
        $this->assertType('Zend_Db_Table_Row_Abstract', $row,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(4, $row->$bug_id);
    }

    public function testTableFetchRowEmpty()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');

        $row = $table->fetchRow("$bug_id = -1");
        $this->assertEquals(null, $row,
            'Expecting null result for non-existent row');
    }

    public function testTableFetchAll()
    {
        $table = $this->_getTableById('Bugs');
        $rowset = $table->fetchAll();
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(4, count($rowset));
        $row1 = $rowset->current();
        $this->assertType('Zend_Db_Table_Row_Abstract', $row1,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row1));
    }

    public function testTableFetchAllWhere()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');

        $rowset = $table->fetchAll("$bug_id = 2");
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(1, count($rowset));
        $row1 = $rowset->current();
        $this->assertType('Zend_Db_Table_Row_Abstract', $row1,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row1));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row1->$bug_id);
    }

    public function testTableFetchAllWhereSelect()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');
        $select = $table->select()
            ->where("$bug_id = ?", 2);

        $rowset = $table->fetchAll($select);
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(1, count($rowset));
        $row1 = $rowset->current();
        $this->assertType('Zend_Db_Table_Row_Abstract', $row1,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row1));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row1->$bug_id);
    }

    public function testTableFetchAllOrder()
    {
        $table = $this->_getTableById('Bugs');
        $rowset = $table->fetchAll(null, 'bug_id DESC');
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(4, count($rowset));
        $row1 = $rowset->current();
        $this->assertType('Zend_Db_Table_Row_Abstract', $row1,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row1));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(4, $row1->$bug_id);
    }

    public function testTableFetchAllOrderSelect()
    {
        $table = $this->_getTableById('Bugs');
        $select = $table->select()
            ->order('bug_id DESC');

        $rowset = $table->fetchAll($select);
        $this->assertType('Zend_Db_Table_Rowset_Abstract', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset_Abstract, got '.get_class($rowset));
        $this->assertEquals(4, count($rowset));
        $row1 = $rowset->current();
        $this->assertType('Zend_Db_Table_Row_Abstract', $row1,
            'Expecting object of type Zend_Db_Table_Row_Abstract, got '.get_class($row1));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(4, $row1->$bug_id);
    }

    public function testTableFetchAllOrderExpr()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');

        $rowset = $table->fetchAll(null, new Zend_Db_Expr("$bug_id + 1 DESC"));
        $this->assertType('Zend_Db_Table_Rowset', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset, got '.get_class($rowset));
        $this->assertEquals(4, count($rowset));
        $row1 = $rowset->current();
        $this->assertType('Zend_Db_Table_Row', $row1,
            'Expecting object of type Zend_Db_Table_Row, got '.get_class($row1));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(4, $row1->$bug_id);
    }

    public function testTableFetchAllLimit()
    {
        $table = $this->_getTableById('Bugs');
        $rowset = $table->fetchAll(null, 'bug_id ASC', 2, 1);
        $this->assertType('Zend_Db_Table_Rowset', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset, got '.get_class($rowset));
        $this->assertEquals(2, count($rowset));
        $row1 = $rowset->current();
        $this->assertType('Zend_Db_Table_Row', $row1,
            'Expecting object of type Zend_Db_Table_Row, got '.get_class($row1));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row1->$bug_id);
    }

    public function testTableFetchAllLimitSelect()
    {
        $table = $this->_getTableById('Bugs');
        $select = $table->select()
            ->order('bug_id ASC')
            ->limit(2, 1);

        $rowset = $table->fetchAll($select);
        $this->assertType('Zend_Db_Table_Rowset', $rowset,
            'Expecting object of type Zend_Db_Table_Rowset, got '.get_class($rowset));
        $this->assertEquals(2, count($rowset));
        $row1 = $rowset->current();
        $this->assertType('Zend_Db_Table_Row', $row1,
            'Expecting object of type Zend_Db_Table_Row, got '.get_class($row1));
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $this->assertEquals(2, $row1->$bug_id);
    }

    public function testTableFetchAllEmpty()
    {
        $bug_id = $this->sharedFixture->dbAdapter->quoteIdentifier('bug_id', true);

        $table = $this->_getTableById('Bugs');

        $rowset = $table->fetchAll("$bug_id = -1");
        $this->assertEquals(0, count($rowset));
    }

    public function testTableLoadsCustomRowClass()
    {
        $this->sharedFixture->tableUtility->useMyIncludePath();
        
        if (class_exists('My_ZendDbTable_Row_TestMyRow')) {
            $this->markTestSkipped("Cannot test loading the custom Row class because it is already loaded");
            return;
        }

        $this->assertFalse(class_exists('My_ZendDbTable_Row_TestMyRow', false),
            'Expected TestMyRow class not to be loaded (#1)');
        $this->assertFalse(class_exists('My_ZendDbTable_Rowset_TestMyRowset', false),
            'Expected TestMyRowset class not to be loaded (#1)');

        // instantiating the table does not creat a rowset
        // so the custom classes are not loaded yet
        $bugsTable = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugsCustom');

        $this->assertFalse(class_exists('My_ZendDbTable_Row_TestMyRow', false),
            'Expected TestMyRow class not to be loaded (#2)');
        $this->assertFalse(class_exists('My_ZendDbTable_Rowset_TestMyRowset', false),
            'Expected TestMyRowset class not to be loaded (#2)');
        
        // creating a rowset makes the table load the rowset class
        // and the rowset constructor loads the row class.
        $bugs = $bugsTable->fetchAll();

        $this->assertTrue(class_exists('My_ZendDbTable_Row_TestMyRow', false),
            'Expected TestMyRow class to be loaded (#3)');
        $this->assertTrue(class_exists('My_ZendDbTable_Rowset_TestMyRowset', false),
            'Expected TestMyRowset class to be loaded (#3)');
    }

    /**
     * Ensures that Zend_Db_Table_Abstract::setDefaultMetadataCache() performs as expected
     *
     * @return void
     */
    public function testTableSetDefaultMetadataCache()
    {
        $cache = $this->_getCache();

        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

        $this->assertSame($cache, Zend_Db_Table_Abstract::getDefaultMetadataCache());

        Zend_Db_Table_Abstract::setDefaultMetadataCache();

        $this->assertNull(Zend_Db_Table_Abstract::getDefaultMetadataCache());
    }

    public function testTableSetDefaultMetadataCacheRegistry()
    {
        $cache = $this->_getCache();
        Zend_Registry::set('registered_metadata_cache', $cache);
        Zend_Db_Table_Abstract::setDefaultMetadataCache('registered_metadata_cache');
        $this->assertSame($cache, Zend_Db_Table_Abstract::getDefaultMetadataCache());
    }

    public function testTableMetadataCacheRegistry()
    {
        $cache = $this->_getCache();

        Zend_Registry::set('registered_metadata_cache', $cache);

        $tableBugsCustom1 = $this->sharedFixture->tableUtility->getTable(
            'My_ZendDbTable_TableBugsCustom',
            array('metadataCache' => 'registered_metadata_cache')
        );

        $this->assertSame($cache, $tableBugsCustom1->getMetadataCache());
    }

    public function testTableSetDefaultMetadataCacheWriteAccess()
    {
        $cache = $this->_getCacheNowrite();
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

        try {
            $bugsTable = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugs');
            $primary = $bugsTable->info(Zend_Db_Table_Abstract::PRIMARY);
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e);
            $this->assertEquals('Failed saving metadata to metadataCache', $e->getMessage());
        }

        Zend_Db_Table_Abstract::setDefaultMetadataCache(null);
    }

    /**
     * Ensures that table metadata caching works as expected when the cache object
     * is set in the configuration for a new table object.
     *
     * @return void
     */
    public function testTableMetadataCacheNew()
    {
        $cache = $this->_getCache();

        $tableBugsCustom1 = $this->sharedFixture->tableUtility->getTable(
            'My_ZendDbTable_TableBugsCustom',
            array('metadataCache' => $cache)
        );

        $this->assertType(
            'Zend_Cache_Core',
            $tableBugsCustom1->getMetadataCache()
        );

        $this->assertFalse($tableBugsCustom1->isMetadataFromCache, 'Failed asserting metadata is not from cache');

        $tableBugsCustom1->setup();

        $this->assertTrue($tableBugsCustom1->isMetadataFromCache, 'Failed asserting metadata is from cache');

        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

        $tableBugsCustom1->setup();

        $this->assertFalse($tableBugsCustom1->isMetadataFromCache, 'Failed asserting metadata is not from cache after cleaning');
    }

    /**
     * Ensures that table metadata caching can be persistent in the object even
     * after a flushed cache, if the setMetadataCacheInClass property is true.
     *
     * @group  ZF-2510
     * @return void
     */
    public function testTableMetadataCacheInClass()
    {
        $cache = $this->_getCache();

        $tableBugsCustom1 = $this->sharedFixture->tableUtility->getTable(
            'My_ZendDbTable_TableBugsCustom',
            array(
                'metadataCache'        => $cache,
                'metadataCacheInClass' => true,
            )
        );

        $this->assertType(
            'Zend_Cache_Core',
            $tableBugsCustom1->getMetadataCache()
        );

        $this->assertFalse($tableBugsCustom1->isMetadataFromCache, 'Failed asserting metadata is not from cache');

        $tableBugsCustom1->setup();

        $this->assertTrue($tableBugsCustom1->isMetadataFromCache, 'Failed asserting metadata is from cache');

        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

        $tableBugsCustom1->setup();

        $this->assertTrue($tableBugsCustom1->isMetadataFromCache, 'Failed asserting metadata is from cache after cleaning');
    }

    /**
     * Ensures that table metadata caching works as expected when the default cache object
     * is set for the abstract table class.
     *
     * @return void
     */
    public function testTableMetadataCacheClass()
    {
        $cache = $this->_getCache();

        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

        $tableBugsCustom1 = $this->sharedFixture->tableUtility->getTable('My_ZendDbTable_TableBugsCustom');

        $this->assertFalse($tableBugsCustom1->isMetadataFromCache);

        $this->assertType(
            'Zend_Cache_Core',
            $tableBugsCustom1->getMetadataCache()
        );

        $tableBugsCustom1->setup();

        $this->assertTrue($tableBugsCustom1->isMetadataFromCache);

        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

        $tableBugsCustom1->setup();

        $this->assertFalse($tableBugsCustom1->isMetadataFromCache);
    }

    public function testTableSetDefaultMetadataCacheException()
    {
        try {
            Zend_Db_Table_Abstract::setDefaultMetadataCache(new stdClass());
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals("Argument must be of type Zend_Cache_Core, or a Registry key where a Zend_Cache_Core object is stored", $e->getMessage());
        }

        try {
            Zend_Db_Table_Abstract::setDefaultMetadataCache(327);
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals("Argument must be of type Zend_Cache_Core, or a Registry key where a Zend_Cache_Core object is stored", $e->getMessage());
        }
    }

    public function testTableMetadataCacheException()
    {
        $this->sharedFixture->tableUtility->useMyIncludePath();
        Zend_Loader::loadClass('My_ZendDbTable_TableBugs');

        /**
         * options array points 'metadataCache' to integer scalar
         */
        try {
            $table = new My_ZendDbTable_TableBugs(array('metadataCache' => 327));
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals("Argument must be of type Zend_Cache_Core, or a Registry key where a Zend_Cache_Core object is stored", $e->getMessage());
        }

        /**
         * options array points 'metadataCache' to Registry key containing integer scalar
         */
        Zend_Registry::set('registered_metadata_cache', 327);
        try {
            $table = new My_ZendDbTable_TableBugs(array('metadataCache' => 'registered_metadata_cache'));
            $this->fail('Expected to catch Zend_Db_Table_Exception');
        } catch (Zend_Exception $e) {
            $this->assertType('Zend_Db_Table_Exception', $e,
                'Expecting object of type Zend_Db_Table_Exception, got '.get_class($e));
            $this->assertEquals("Argument must be of type Zend_Cache_Core, or a Registry key where a Zend_Cache_Core object is stored", $e->getMessage());
        }
    }

    public function testTableCascadeUpdate()
    {
        $table = $this->_getTableById('Products');
        $row1 = $table->find(1)->current();
        $rows1 = $row1->findManyToManyRowset('My_ZendDbTable_TableBugs', 'My_ZendDbTable_TableBugsProducts');

        $product_id = $this->sharedFixture->dbAdapter->foldCase('product_id');
        $row1->$product_id = 999999;
        $row1->save();
        $rows2 = $row1->findManyToManyRowset('My_ZendDbTable_TableBugs', 'My_ZendDbTable_TableBugsProducts');

        $this->assertEquals(999999, $row1->$product_id);
        $this->assertEquals(count($rows1), count($rows2));

        // Test for 'false' value in cascade config
        $bug_id = $this->sharedFixture->dbAdapter->foldCase('bug_id');
        $row2 = $rows2->current();
        $row2->$bug_id = 999999;
        $row2->save();
    }

    public function testTableCascadeDelete()
    {
        $table = $this->_getTableById('Products');
        $row1 = $table->find(2)->current();
        $row1->delete();

        // Test for 'false' value in cascade config
        $table = $this->_getTableById('Bugs');
        $row2 = $table->find(1)->current();
        $row2->delete();

        $table = $this->_getTableById('BugsProducts');
        $product_id = $this->sharedFixture->dbAdapter->quoteIdentifier('product_id', true);
        $select = $table->select()
            ->where($product_id . ' = ?', 2);

        $rows = $table->fetchAll($select);
        $this->assertEquals(0, count($rows));
    }

    public function testSerialiseTable()
    {
        $clonedUtility = $this->_getClonedUtility();
        $dbAdapter = $clonedUtility->getDbAdapter();
        $table = $this->_getTableById('Products', array('db' => $dbAdapter));
        $this->assertType('string', serialize($table));
    }
    
    /**
     * @group ZF-1343
     */
    public function testTableFetchallCanHandleWhereWithParameritizationCharacters()
    {
        $table = $this->_getTableById('Products');
        $column = $table->getAdapter()->quoteIdentifier('product_name');
        $where = $table->getAdapter()->quoteInto("$column = ?", "some?product's");
        $rows = $table->fetchAll($where);
        $this->assertEquals(0, count($rows));
    }

    /**
     * Returns a clean Zend_Cache_Core with File backend
     *
     * @return Zend_Cache_Core
     */
    protected function _getCache()
    {
        /**
         * @see Zend_Cache
         */
        require_once 'Zend/Cache.php';

        $folder = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'cachefiles';

        $frontendOptions = array(
            'automatic_serialization' => true
        );

        $backendOptions  = array(
            'cache_dir'                 => $folder,
            'file_name_prefix'          => 'Zend_Db_Table_AbstractTestCase'
        );

        $cacheFrontend = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

        $cacheFrontend->clean(Zend_Cache::CLEANING_MODE_ALL);

        return $cacheFrontend;
    }

    /**
     * Returns a clean Zend_Cache_Core with File backend
     *
     * @return Zend_Cache_Core
     */
    protected function _getCacheNowrite()
    {
        /**
         * @see Zend_Cache
         */
        require_once 'Zend/Cache.php';

        $folder = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'nofiles';
        if (!file_exists($folder)) {
            mkdir($folder, 0777);
        }

        $frontendOptions = array(
            'automatic_serialization' => true
        );

        $backendOptions  = array(
            'cache_dir'                 => $folder,
            'file_name_prefix'          => 'Zend_Db_Table_AbstractTestCase'
        );

        $cacheFrontend = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

        $cacheFrontend->clean(Zend_Cache::CLEANING_MODE_ALL);

        rmdir($folder);

        return $cacheFrontend;
    }

}
