<?php

namespace zend\cache\storagePlugin;
use \zend\cache\storageAdapter\StorageAdapterInterface as StorageAdapterInterface;
use \zend\cache\InvalidArgumentException as InvalidArgumentException;

class Tagging extends StoragePluginAbstract
{

    /**
     * The tag storage
     *
     * @var \zend\cache\storageAdapter\StorageAdapterInterface
     */
    protected $_tagStorage = null;

    /**
     * Key-Prefix of key files
     * - all tags of one key "<prefix><key>"
     *
     * @var string
     */
    protected $_key2TagPrefix = 'zf-key2tag-';

    /**
     * Key-Prefix of tag files
     * - all keys of one tag "<prefix><tag>"
     *
     * @var string
     */
    protected $_tag2IdPrefix = 'zf-tag2key-';

    public function getTagStorage()
    {
        if ($this->_tagHandler === null) {
            return $this;
        }

        return $this->_tagStorage;
    }

    public function setTagStorage(StorageAdapterInterface $tagStorage)
    {
        $this->_tagStorage = $tagStorage;
    }

    public function resetTagHandler()
    {
        $this->_tagHandler = null;
    }

    public function getKey2TagPrefix()
    {
        return $this->_key2tagPrefix;
    }

    public function setKey2TagPrefix($prefix)
    {
        $prefix = (string)$prefix;
        if (!$prefix) {
            throw new InvalidArgumentException('The key2tagPrefix can\'t be empty');
        }

        $this->_key2TagPrefix = $prefix;
    }

    public function getTag2KeyPrefix()
    {
        return $this->_tag2KeyPrefix;
    }

    public function setTag2KeyPrefix($prefix)
    {
        $prefix = (string)$prefix;
        if (!$prefix) {
            throw new InvalidArgumentException('The tag2KeyPrefix can\'t be empty');
        }

        $this->_tag2KeyPrefix = $prefix;
    }

    public function getCapabilities()
    {
        $capabilities = $this->getStorage()->getCapabilities();
        $capabilities['tagging'] = true;
        return $capabilities;
    }

    // implement tagging

}