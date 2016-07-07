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
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Select;

class Collection extends XhbCollection implements \Xhb\Model\Resource\Iface\Operation\Collection
{
    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_init(\Xhb\Model\Xhb::MODEL_CLASS_NAMESPACE . 'Operation', 'id', Operation::MAIN_TABLE);
    }

    protected function _beforeLoad() {
        if (!$this->getFlag('skip_aggregated_fields')) {
            $cols = array(
                'main_table.info',
                'main_table.wording',
                'main_table.smem',
                '(SELECT IFNULL((SELECT name FROM category AS parent_category WHERE key = category.parent), "") || ":" || name
                    FROM category WHERE key = main_table.category)',
                '(SELECT name FROM payee WHERE key = main_table.payee)',
                '(SELECT GROUP_CONCAT(c.name, "|") FROM category c WHERE c.key IN
                    (SELECT osa.category FROM ' . Operation::SPLIT_AMOUNT_TABLE . ' osa WHERE osa.operation_id = main_table.id))'
            );
            $colsSep = array();
            foreach($cols as $c) {
                $colsSep[] = "IFNULL($c, \"\")";
                $colsSep[] = ' "|" ';
            }
            $expr = new Expression(implode(' || ', $colsSep));
            $this->addFieldToSelect(array('aggregate_search' => $expr));
        }
        return parent::_beforeLoad();
    }

    public function getBalance() {
        $balance = 0;
        foreach($this as $op) {
            $balance += $op->getAmount();
        }
        return $balance;
    }

    protected function _filterToPredicate($field, $predicate) {
        if (strtolower($field) == 'categories' && !$predicate instanceof Predicate) {
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