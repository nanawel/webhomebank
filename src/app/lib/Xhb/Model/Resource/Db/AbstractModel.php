<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace Xhb\Model\Resource\Db;

use DB\SQL;
use Xhb\Model\Resource\Iface\Model as ResourceModel;
use Xhb\Util\MagicObject;
use Laminas\Db\Adapter\Adapter;

/**
 * Class AbstractModel
 *
 * @method \Laminas\Db\Adapter\Adapter getConnection()
 * @method \Xhb\Model\Xhb getXhb()
 *
 * @package Xhb\Model\Resource\Db
 */
abstract class AbstractModel extends MagicObject implements ResourceModel
{
    /**
     * @var string
     */
    protected $_mainTable;

    /**
     * @var string|string[]
     */
    protected $_keyField;

    public function __construct(array $params = []) {
        parent::__construct($params);
        if (!isset($params['resource_config']['db'])) {
            throw new \Exception('Missing DB config');
        }

        $this->setDb(new Adapter($params['resource_config']['db']));
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
        // Remove objects linked to PDO (not serializable)
        $this->unsetData('db');
        $this->unsetData('sql');
    }

    protected function _init($keyField, $tableName) {
        $this->_mainTable = $tableName;
        $this->_keyField = is_array($keyField) ? $keyField : [$keyField];
    }

    public function load(MagicObject $object, $id) {
        if (!is_array($id) && count($this->_keyField) == 1) {
            $id = [$this->_keyField[0] => $id];
        }

        if (count($id) !== $fieldsCount = count($this->_keyField)) {
            throw new \Exception(sprintf('Invalid ID specified (should have %s fields)', $fieldsCount));
        }

        foreach($this->_keyField as $kf) {
            $object->setData($kf, $id[$kf]);
        }

        $select = $this->getSql()->select();
        $select->columns(['*']);
        foreach($this->_keyField as $field) {
            if (!isset($id[$field])) {
                throw new \Exception('Missing field "' . $field . '" in ID');
            }

            $select->where([$field => $id[$field]]);
        }

        $select->limit(1);

        $result = $this->getDb()->query($this->getSql()->buildSqlString($select), Adapter::QUERY_MODE_EXECUTE);
        $data = $result->current();
        if ($data) {
            $object->addData((array) $data);
        }

        return $this;
    }

    public function save(MagicObject $object) {
        throw new \Exception(__METHOD__ . ': Not supported');
    }

    public function delete(MagicObject $object) {
        throw new \Exception(__METHOD__ . ': Not supported');
    }
}
