<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace Xhb\Model\Resource\Db;

use app\models\core\Log;
use Laminas\Db\Adapter\Profiler\Profiler;
use Xhb\Model\Resource\Closure;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Predicate\In;
use Laminas\Db\Sql\Predicate\IsNull;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Predicate\NotIn;
use Laminas\Db\Sql\Predicate\NotLike;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Select;

/**
 * Class AbstractCollection
 *
 * Default in-memory collection implementation.
 *
 * @method \Laminas\Db\Adapter\Adapter getConnection()
 *
 * @package Xhb\Model\Resource
 */
abstract class AbstractCollection extends \Xhb\Model\Resource\AbstractCollection
{
    public $_mainTable;

    /**
     * @var Profiler
     */
    protected static $_profiler;

    /**
     * @var string
     */
    protected $_table;

    /**
     * @var \Laminas\Db\Sql\Select
     */
    protected $_select;

    /**
     * @var array
     */
    protected $_columns = [Select::SQL_STAR];

    public function __construct(array $params = []) {
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
        $this->_select->from(['main_table' => $tableName]);
    }

    /**
     * @return \Laminas\Db\Adapter\Adapter
     */
    public function getDb() {
        if (! $db = $this->getData('db')) {
            throw new \Exception('Missing DB adapter');
        }

        return $db;
    }

    /**
     * @return \Laminas\Db\Sql\Sql
     */
    public function getSql() {
        if (!$this->getData('sql')) {
            $this->setSql(new \Laminas\Db\Sql\Sql($this->getDb(), $this->_mainTable));
        }

        return $this->getData('sql');
    }

    public function __sleep() {
        $this->unsetData('sql');    // Remove objects linked to PDO (not serializable)
    }

    /**
     * @return \Laminas\Db\Sql\Select
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

        self::getProfiler()->profilerStart($sql);
        $items = $this->getDb()->query($sql, Adapter::QUERY_MODE_EXECUTE);
        self::getProfiler()->profilerFinish();

        //Log::instance()->log(print_r(self::getProfiler()->getLastProfile(), true), LOG_DEBUG);

        return $items->toArray();
    }

    public function addFieldToSelect($field) {
        if (!is_array($field)) {
            $field = [$field => $field];
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
            $predicate = ['eq' => $predicate];
        }

        $operator = key($predicate);
        $value = current($predicate);
        $predicate = match ($operator) {
            'eq', '=', '==' => new Operator($field, Operator::OP_EQ, $value),
            'ne', 'neq', '!=' => new Operator($field, Operator::OP_NE, $value),
            'gt', '>' => new Operator($field, Operator::OP_GT, $value),
            'ge', '>=' => new Operator($field, Operator::OP_GTE, $value),
            'lt', '<' => new Operator($field, Operator::OP_LT, $value),
            'le', '<' => new Operator($field, Operator::OP_LTE, $value),
            'null' => new IsNull($field),
            'in' => new In($field, $value),
            'nin' => new NotIn($field),
            'like' => new Like($field, $value),
            'nlike' => new NotLike($field, $value),
            default => throw new \InvalidArgumentException('"' . $operator . '" is not a valid operator'),
        };

        return $predicate;
    }

    protected function _applyOrder() {
        if (!empty($this->_orders)) {
            $orderBy = [];
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

    protected static function getProfiler() {
        if (!self::$_profiler) {
            self::$_profiler = new Profiler();
        }

        return self::$_profiler;
    }
}
