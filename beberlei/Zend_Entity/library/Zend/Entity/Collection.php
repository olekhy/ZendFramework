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

class Zend_Entity_Collection implements Zend_Entity_Collection_Interface
{
    /**
     * All entries that have been added to the collection by the userland.
     *
     * This data is used for a diff of the collection during commit.
     *
     * @var array
     */
    protected $_added = array();

    /**
     * All entries that have been removed from the collection in userland.
     *
     * This data is used for a diff of the collection during commit.
     *
     * @var array
     */
    protected $_removed = array();

    /**
     * @var array
     */
    protected $_collection;

    /**
     * @var string
     */
    protected $_entityClassType = null;

    /**
     * Create a new collection.
     * 
     * @param object[] $collection
     * @param string $entityClassType
     */
    public function __construct(array $collection=array(), $entityClassType=null)
    {
        $this->_collection      = $collection;
        $this->_entityClassType = $entityClassType;
    }

    /**
     * Is this entity of the correct type?
     * 
     * @param mixed $entity
     */
    protected function assertEntityIsOfCorrectType($entity)
    {
        if($this->_entityClassType !== null && !($entity instanceof $this->_entityClassType)) {
            require_once "Zend/Entity/Exception.php";
            throw new Zend_Entity_Exception(
                "Cannot add entity of type '".get_class($entity)."' to list that ".
                "expects '".$this->_entityClassType."'"
            );
        }
    }

    public function current()
    {
        return current($this->_collection);
    }

    public function valid()
    {
        return ($this->current()!==false);
    }

    public function next()
    {
        return next($this->_collection);
    }

    public function key()
    {
        return key($this->_collection);
    }

    public function rewind()
    {
        return reset($this->_collection);
    }

    public function count()
    {
        return count($this->_collection);
    }

    public function offsetExists($offset)
    {
        return isset($this->_collection[$offset]);
    }

    public function offsetGet($offset)
    {
        if(isset($this->_collection[$offset])) {
            return $this->_collection[$offset];
        }
        return false;
    }

    public function offsetSet($offset, $entity)
    {
        $this->assertEntityIsOfCorrectType($entity);

        $this->_added[] = $entity;
        // $col[] syntax leads to $offset == null => 0
        if($offset !== null) {
            $this->_collection[$offset] = $entity;
        } else {
            $this->_collection[] = $entity;
        }
    }
    
    public function offsetUnset($offset)
    {
        if(isset($this->_collection[$offset])) {
            $this->_removed[] = $this->_collection[$offset];
            unset($this->_collection[$offset]);
        }
    }

    public function __ze_getRemoved()
    {
        return $this->_removed;
    }

    public function __ze_getAdded()
    {
        return $this->_added;
    }

    public function __ze_wasLoadedFromDatabase()
    {
        return true;
    }
}