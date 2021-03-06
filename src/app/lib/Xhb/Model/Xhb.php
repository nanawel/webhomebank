<?php
namespace Xhb\Model;

use Xhb\Model\Resource\AbstractCollection;
use Xhb\Model\Xhb\DateHelper;

class Xhb extends XhbModel
{
    protected $_resourceType;

    protected $_accounts    = null;
    protected $_categories  = null;
    protected $_payees      = null;
    protected $_operations  = null;

    protected $_dateHelper  = null;

    public function __construct($data = array()) {
        parent::__construct($data);
        if (isset($data['resource_config']['type']) && $data['resource_config']['type']) {
            $this->_resourceType = ucfirst($data['resource_config']['type']);
        }
        else {
            throw new \Exception('Missing resource configuration');
        }
        $this->setXhb($this);

        // Init own resource instance
        $this->getResource(true, $data);
    }

    public function getXhbId() {
        return $this->getId();
    }

    /**
     * @return Account
     */
    public function getAccount($id) {
        if ($id instanceof Account) {
            $id = $id->getId();
        }
        return $this->_getAccountCollection()->getItem($id);
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getAccountCollection() {
        return $this->getResource()->getAccountCollection();
    }

    /**
     * Load internal full collection.
     *
     * @return AbstractCollection
     */
    protected function _getAccountCollection() {
        if ($this->_accounts === null) {
            $this->_accounts = $this->getAccountCollection();
        }
        return $this->_accounts;
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getCategoryCollection() {
        return $this->getResource()->getCategoryCollection();
    }

    /**
     * Load internal full collection.
     *
     * @return AbstractCollection
     */
    protected function _getCategoryCollection() {
        if ($this->_categories === null) {
            $this->_categories = $this->getCategoryCollection();
        }
        return $this->_categories;
    }

    /**
     * @return Category
     */
    public function getCategory($id) {
        if ($id instanceof Category) {
            $id = $id->getId();
        }
        return $this->_getCategoryCollection()->getItem($id);
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getOperationCollection() {
        return $this->getResource()->getOperationCollection();
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getPayeeCollection() {
        return $this->getResource()->getPayeeCollection();
    }

    /**
     * Load internal full collection.
     *
     * @return AbstractCollection
     */
    protected function _getPayeeCollection() {
        if ($this->_payees === null) {
            $this->_payees = $this->getPayeeCollection();
        }
        return $this->_payees;
    }

    /**
     * @return Payee
     */
    public function getPayee($id) {
        if ($id instanceof Payee) {
            $id = $id->getId();
        }
        return $this->_getPayeeCollection()->getItem($id);
    }

    public function getDateHelper($singleton = true) {
        if (! $singleton) {
            return new DateHelper(array('xhb' => $this));
        }
        if (! $this->_dateHelper) {
            $this->_dateHelper = new DateHelper(array('xhb' => $this));
        }
        return $this->_dateHelper;
    }

    /*
     * Resources Management
     */

    public function getResourceClass($modelClass) {
        $fullClassName = self::MODEL_CLASS_NAMESPACE . 'Resource\\' . $this->_resourceType . '\\' . $modelClass;
        return $fullClassName;
    }

    public function getResourceInstance($modelClass, $params = array()) {
        $fullClassName = $this->getResourceClass($modelClass);
        return new $fullClassName($params);
    }

    public function getCollectionInstance($modelClass, $params = array()) {
        $coll = $this->getResourceInstance($modelClass . '\\Collection', $params);
        $coll->setXhb($this->getXhb());
        return $coll;
    }
}