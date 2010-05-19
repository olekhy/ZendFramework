<?php
/**
 * Mapper
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * 
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so we can send you a copy immediately.
 *
 * @category   Zend
 * @category   Zend_Entity
 * @copyright  Copyright (c) 2009 Benjamin Eberlei
 * @license    New BSD License
 */

class Zend_Entity_Definition_PrimaryKey extends Zend_Entity_Definition_Property
{
    /**
     * @var Zend_Entity_Definition_Id_Interface
     */
    public $generator;

    /**
     * Get Key String identifier
     * 
     * @return string
     */
    public function getKey()
    {
        return $this->getColumnName();
    }

    /**
     * Build a where condition for finding a specific entity using the primary key.
     * 
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  string $tableName
     * @param  string $key
     * @return string
     */
    public function buildWhereCondition(Zend_Db_Adapter_Abstract $db, $tableName, $key)
    {
        $whereCondition = $db->quoteInto(
            sprintf('%s.%s = ?', $tableName, $this->columnName),
            $key
        );

        return $whereCondition;
    }

    /**
     * Get Id Generator
     * 
     * @return Zend_Entity_Definition_Id_Interface
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * Set Id Generator
     *
     * @param Zend_Entity_Definition_Id_Interface $generator
     */
    public function setGenerator(Zend_Entity_Definition_Id_Interface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Apply the net sequence id as column of the entities database state.
     * 
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  array $entityDatabaseState
     * @return array
     */
    public function applyNextSequenceId(Zend_Db_Adapter_Abstract $db, array $entityDatabaseState)
    {
        return array(
            $this->getColumnName() => $this->generator->nextSequenceId($db)
        );
    }

    /**
     *
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  array $entityDatabaseState
     * @return array
     */
    public function lastSequenceId(Zend_Db_Adapter_Abstract $db, array $entityDatabaseState)
    {
        return $this->generator->lastSequenceId($db);
    }

    /**
     * @param  array $databaseState
     * @return array
     */
    public function removeSequenceFromState($databaseState)
    {
        $columnKey = $this->getColumnName();
        if(isset($databaseState[$columnKey])) {
            unset($databaseState[$columnKey]);
        }
        return $databaseState;
    }

    /**
     * Get an array with a null key property.
     * 
     * @return array
     */
    public function getEmptyKeyProperties()
    {
        return array($this->getPropertyName() => null);
    }

    /**
     * Return array of primary key fields values of the database state.
     *
     * @param  array $row
     * @return string
     */
    public function retrieveKeyValuesFromProperties(array $row)
    {
        return $row[$this->getColumnName()];
    }
}