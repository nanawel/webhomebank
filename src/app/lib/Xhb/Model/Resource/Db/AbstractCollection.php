<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace Xhb\Model\Resource\Db;

use Xhb\Model\Resource\Closure;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\IsNull;
use Zend\Db\Sql\Predicate\Like;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Predicate\NotLike;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Select;

/**
 * Class AbstractCollection
 *
 * Default in-memory collection implementation.
 *
 * @method \Zend\Db\Adapter\Adapter getConnection()
 *
 * @package Xhb\Model\Resource
 */
abstract class AbstractCollection extends \Xhb\Model\Resource\AbstractCollection
{
    /**
     * @var string
     */
    protected $_table;

    /**
     * @var \Zend\Db\Sql\Select
     */
    protected $_select;

    /**
     * @var array
     */
    protected $_columns = array(Select::SQL_STAR);

    public function __construct(array $params = array()) {
        parent::__construct($params);
        if (!isset($params['resource_config']['db'])) {
            throw new \Exception('Missing DB config');
        }
        $this->setDb(new Adapter($params['resource_config']['db']));
    }

    protected function _init($itemClass, $keyField, $tableName = '') {
        parent::_init($itemClass, $keyField);
        $this->_table = $tableName;
        $this->_select = new Select();
        $this->_select->from(array('main_table' => $tableName));
    }

    /**
     * @return \Zend\Db\Adapter\Adapter
     */
    public function getDb() {
        if (! $db = $this->getData('db')) {
            throw new \Exception('Missing DB adapter');
        }
        return $db;
    }

    /**
     * @return \Zend\Db\Sql\Sql
     */
    public function getSql() {
        if (!$this->getData('sql')) {
            $this->setSql(new \Zend\Db\Sql\Sql($this->getDb(), $this->_mainTable));
        }
        return $this->getData('sql');
    }

    function __sleep() {
        $this->unsetData('sql');    // Remove objects linked to PDO (not serializable)
    }

    /**
     * @return \Zend\Db\Sql\Select
     */
    public function getSelect() {
        $this->_select->columns($this->_columns);
        return $this->_select;
    }

    /**
     * For debug purposes only
     *
     * @return string
     */
    public function dumpSelect() {
        $currentSelect = $this->_select;
        $this->_select = clone $currentSelect;
        $this->_beforeLoad()
            ->_applyFilters()
            ->_applyOrder()
            ->_applyLimit()
            ->_load();
        $selectSql = $this->getSql()->buildSqlString($this->getSelect());
        $this->_select = $currentSelect;
        return $selectSql;
    }

    protected function _load() {
        $items = $this->_execLoadQuery();
        foreach($items as $it) {
            $item = new $this->_itemClass((array) $it);
            $this->addItem($item);
        }
        return $this;
    }

    protected function _execLoadQuery() {
        $sql = $this->getSql()->buildSqlString($this->getSelect());
        if ($this->hasData('sql_dumper') && is_callable($dumper = $this->getData('sql_dumper'))) {
            call_user_func($dumper, $sql);
        }
        $items = $this->getDb()->query($sql, Adapter::QUERY_MODE_EXECUTE);
        return $items->toArray();
    }

    public function addFieldToSelect($field) {
        if (!is_array($field)) {
            $field = array($field => $field);
        }
        foreach($field as $correlationName => $fieldName) {
            $this->_columns[$correlationName] = $fieldName;
        }
        return $this;
    }

    protected function _applyFilters() {
        foreach($this->_filters as $filter) {
            $predicate = null;
            if ($filter instanceof Operator) {
                $predicate = $filter;
            }
            else {
                $field = key($filter);
                $cond = current($filter);
                $predicate = $this->_filterToPredicate($field, $cond);
            }
            $this->_select->where($predicate);
        }
        return $this;
    }

    /**
     * @param $field string
     * @param $predicate array|string
     * @return Predicate
     */
    protected function _filterToPredicate($field, $predicate) {
        if (!is_array($predicate)) {
            $predicate = array('eq' => $predicate);
        }
        $operator = key($predicate);
        $value = current($predicate);
        switch($operator) {
            case 'eq':
            case '=':
            case '==':
                $predicate = new Operator($field, Operator::OP_EQ, $value);
                break;

            case 'ne':
            case 'neq':
            case '!=':
                $predicate = new Operator($field, Operator::OP_NE, $value);
                break;

            case 'gt':
            case '>':
                $predicate = new Operator($field, Operator::OP_GT, $value);
                break;


            case 'ge':
            case '>=':
                $predicate = new Operator($field, Operator::OP_GTE, $value);
                break;

            case 'lt':
            case '<':
                $predicate = new Operator($field, Operator::OP_LT, $value);
                break;

            case 'le':
            case '<':
                $predicate = new Operator($field, Operator::OP_LTE, $value);
                break;

            case 'null':
                $predicate = new IsNull($field);
                break;

            case 'in':
                $predicate = new In($field, $value);
                break;

            case 'nin':
                $predicate = new NotIn($field);
                break;

            case 'like':
                $predicate = new Like($field, $value);
                break;

            case 'nlike':
                $predicate = new NotLike($field, $value);
                break;

            default:
                throw new \InvalidArgumentException('"' . $operator . '" is not a valid operator');
        }
        return $predicate;
    }

    protected function _applyOrder() {
        if (!empty($this->_orders)) {
            $orderBy = array();
            foreach ($this->_orders as $field => $dir) {
                $orderBy[] = $field . ' ' . ($dir == SORT_DESC ? 'DESC' : 'ASC');
            }
            $this->_select->order($orderBy);
        }
        return $this;
    }

    protected function _applyLimit() {
        if ($this->_limit !== false) {
            $this->_select->limit($this->_limit);
        }
        return $this;
    }
}