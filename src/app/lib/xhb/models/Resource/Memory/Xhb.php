<?php
namespace xhb\models\Resource\Memory;

use xhb\models\Account;
use xhb\models\Category;
use xhb\models\Operation;
use xhb\models\Payee;
use xhb\util\MagicObject;

class Xhb extends AbstractModel
{
    /**
     *
     * @return AbstractCollection
     */
    public function getAccountCollection() {
        $accounts = $this->getCollectionInstance('Account');
        foreach($this->getXhbData()['accounts'] as $a) {
            $a['xhb'] = $this->getXhb();
            $accounts->addItem(new Account($a));
        }
        return $accounts;
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getCategoryCollection() {
        $categories = $this->getCollectionInstance('Category');
        foreach($this->getXhbData()['categories'] as $c) {
            $c['xhb'] = $this->getXhb();
            $categories->addItem(new Category($c));
        }
        return $categories;
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getOperationCollection() {
        $operations = $this->getCollectionInstance('Operation');
        foreach($this->getXhbData()['operations'] as $o) {
            $o['xhb'] = $this->getXhb();
            $operations->addItem(new Operation($o));
        }
        return $operations;
    }

    /**
     *
     * @return AbstractCollection
     */
    public function getPayeeCollection() {
        $payees = $this->getCollectionInstance('Payee');
        foreach($this->getXhbData()['payees'] as $p) {
            $p['xhb'] = $this->getXhb();
            $payees->addItem(new Payee($p));
        }
        return $payees;
    }

    public function getCollectionInstance($modelClass, $params = array()) {
        return $this->getXhb()->getCollectionInstance($modelClass, $params);
    }

    public function load(MagicObject $object, $id) {
        $object->addData($this->getXhbData()['properties']);
        return $this;
    }
}