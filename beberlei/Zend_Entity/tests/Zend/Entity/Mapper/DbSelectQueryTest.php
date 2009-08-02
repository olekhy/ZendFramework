<?php

class Zend_Entity_Mapper_DbSelectQueryTest extends Zend_Entity_TestCase
{
    public function testLoaderInitializesSelect()
    {
        $em = $this->createTestingEntityManager();
        $select = $this->getSelectMock();
        $loader = $this->getLoaderMock($select);

        $query = new Zend_Entity_Mapper_DbSelectQuery($select, $loader, $em);
    }

    public function getSelectMock()
    {
        $select = $this->getMock('Zend_Entity_Mapper_Select', array(), array(), '', false);
        $select->expects($this->any())
               ->method('query')
               ->will($this->returnValue(new Zend_Test_DbStatementMock()));
        return $select;
    }

    public function testGetResultList_DelegatesToLoader_ProcessResultset()
    {
        $fixtureReturnValue = "foo";

        $em = $this->createTestingEntityManager();
        $select = $this->getSelectMock();
        $loader = $this->getLoaderMock($select);
        $this->addProcessResultsetExpectation($loader, $fixtureReturnValue, $em);

        $query = new Zend_Entity_Mapper_DbSelectQuery($select, $loader, $em);
        $result = $query->getResultList();

        $this->assertEquals($fixtureReturnValue, $result);
    }

    public function addProcessResultsetExpectation($loader, $returnValue, $em)
    {
        $loader->expects($this->at(2))
               ->method('processResultset')
               ->with($this->isType('array'), $this->equalTo($em))
               ->will($this->returnValue($returnValue));
    }

    public function testGetSingleResult_ReturnValue_IfOneResultOnly()
    {
        $fixtureReturnValue = array( array("foo" => "bar") );

        $result = $this->executeSingleResultQueryWithResult($fixtureReturnValue);

        $this->assertEquals(
            array("foo" => "bar"), $result
        );
    }

    public function testGetSingleResult_ThrowException_WhenMoreThanOneResult()
    {
        $this->setExpectedException("Zend_Entity_Exception");

        $fixtureReturnValue = array( array("foo" => "bar"), array("foo" => "baz") );

        $this->executeSingleResultQueryWithResult($fixtureReturnValue);
    }

    public function testGetSingleResult_ThrowException_WhenNoResult()
    {
        $this->setExpectedException("Zend_Entity_Exception");

        $fixtureReturnValue = array();

        $this->executeSingleResultQueryWithResult($fixtureReturnValue);
    }

    public function executeSingleResultQueryWithResult($fixtureReturnValue)
    {
        $em = $this->createTestingEntityManager();
        $select = $this->getSelectMock();
        $loader = $this->getLoaderMock($select);
        $this->addProcessResultsetExpectation($loader, $fixtureReturnValue, $em);

        $query = new Zend_Entity_Mapper_DbSelectQuery($select, $loader, $em);

        return $query->getSingleResult();
    }

    public function testSetMaxResults()
    {
        $select = $this->getSelectMock();
        $select->expects($this->once())
               ->method('limit')
               ->with(30, null);

        $query = $this->createDbSelectQuery($select);
        $q = $query->setMaxResults(30);

        $this->assertSame($query, $q);
    }

    public function testSetFirstResult()
    {
        $select = $this->getSelectMock();
        $select->expects($this->once())
               ->method('limit')
               ->with(null, 30);

        $query = $this->createDbSelectQuery($select);
        $q = $query->setFirstResult(30);

        $this->assertSame($query, $q);
    }

    public function testSetMaxAndFirstResult()
    {
        $select = $this->getSelectMock();
        $select->expects($this->at(0))
               ->method('limit')
               ->with(30, null);
        $select->expects($this->at(1))
               ->method('limit')
               ->with(30, 30);

        $query = $this->createDbSelectQuery($select);
        $query->setMaxResults(30)->setFirstResult(30);
    }

    public function testGetItemsDelegatesToMaxAndFirstResult()
    {
        $select = $this->getSelectMock();
        $select->expects($this->at(0))
               ->method('limit')
               ->with(30, null);
        $select->expects($this->at(1))
               ->method('limit')
               ->with(30, 30);

        $query = $this->createDbSelectQuery($select);
        $result = $query->getItems(30, 30);
    }

    public function createDbSelectQuery($select)
    {
        $loader = $this->getLoaderMock($select);
        $em = $this->createTestingEntityManager();

        return new Zend_Entity_Mapper_DbSelectQuery($select, $loader, $em);
    }

    public function getLoaderMock($initializeSelect)
    {
        $loader = $this->getMock('Zend_Entity_Mapper_Loader_Interface');
        $loader->expects($this->at(0))->method('initSelect')->with($initializeSelect);
        $loader->expects($this->at(1))->method('initColumns')->with($initializeSelect);
        return $loader;
    }
}