<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:47
 */

namespace xhb\models\Resource\Memory\Operation;

use xhb\models\Resource\Memory\AbstractCollection;

class Collection extends AbstractCollection
{
    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_init('\\xhb\\models\\Operation', 'id');
    }

    protected function _beforeLoad()
    {
        parent::_beforeLoad();

        if (!$this->getFlag('skip_aggregated_fields')) {
            // Generate aggregate field for search
            /* @var $op \xhb\models\Operation */
            foreach($this->_items as $op) {
                $categoryNames = array();
                if ($categories = $op->getCategoryModels()) {
                    foreach($categories as $c) {
                        $categoryNames[] = $c->getFullname();
                    }
                }
                $aggregateSearchField = array(
                    $op->getInfo(),
                    $op->getPayeeModel() ? $op->getPayeeModel()->getName() : '',
                    implode('|', $categoryNames),
                    $op->getMemo(),
                    $op->getSmem()
                );
                $op->setData('aggregate_search', implode('|', $aggregateSearchField));
            }
        }
        return $this;
    }

    public function getBalance() {
        $balance = 0;
        foreach($this as $op) {
            $balance += $op->getAmount();
        }
        return $balance;
    }
}