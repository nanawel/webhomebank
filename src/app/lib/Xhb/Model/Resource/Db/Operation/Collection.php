<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:47
 */

namespace Xhb\Model\Resource\Db\Operation;

use Xhb\Model\Resource\Db\Category;
use Xhb\Model\Resource\Db\Operation;
use Xhb\Model\Resource\Db\XhbCollection;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Select;

class Collection extends XhbCollection implements \Xhb\Model\Resource\Iface\Operation\Collection
{
    public function __construct(array $params = []) {
        parent::__construct($params);
        $this->_init(\Xhb\Model\Xhb::MODEL_CLASS_NAMESPACE . 'Operation', 'id', Operation::MAIN_TABLE);
    }

    public function getBalance() {
        $balance = 0;
        foreach($this as $op) {
            $balance += $op->getAmount();
        }

        return $balance;
    }

    protected function _filterToPredicate($field, $predicate) {
        if (strtolower($field) === 'categories' && !$predicate instanceof Predicate) {
            // Handle split amount
            $predicateSet = new PredicateSet();
            $predicateSet->addPredicate(parent::_filterToPredicate('category', $predicate));
            $secondField = new Expression('(SELECT category FROM ' . Operation::SPLIT_AMOUNT_TABLE
                . ' WHERE operation_id = main_table.id)');
            $predicateSet->addPredicate(parent::_filterToPredicate($secondField, $predicate), Predicate::OP_OR);
            return $predicateSet;
        }

        return parent::_filterToPredicate($field, $predicate);
    }
}
