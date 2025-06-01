<?php

namespace Xhb\Model\Resource\Db;

use DB\SQL;
use Xhb\Util\MagicObject;

class Xhb extends AbstractModel implements \Xhb\Model\Resource\Iface\Xhb
{
    const MAIN_TABLE = 'xhb';

    public function __construct(array $params = []) {
        parent::__construct($params);
        $this->_init('id', self::MAIN_TABLE);
    }

    public function getXhbId() {
        return $this->getXhb()->getId();
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getAccountCollection() {
        return $this->getCollectionInstance('Account');
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getCategoryCollection() {
        return $this->getCollectionInstance('Category');
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getOperationCollection() {
        return  $this->getCollectionInstance('Operation');
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getPayeeCollection() {
        return  $this->getCollectionInstance('Payee');
    }

    public function getCollectionInstance($modelClass, $params = []) {
        $params = array_merge($this->getData(), $params);
        return $this->getXhb()->getCollectionInstance($modelClass, $params);
    }
}