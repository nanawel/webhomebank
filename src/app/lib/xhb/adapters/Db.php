<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 27/08/15
 * Time: 11:28
 */

namespace xhb\adapters;

use DB\SQL;
use xhb\models\Resource\Db\Account;
use xhb\models\Resource\Db\Category;
use xhb\models\Resource\Db\Operation;
use xhb\models\Resource\Db\Payee;
use xhb\models\Resource\Db\Xhb;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Sql\Ddl\Column\Column;
use Zend\Db\Sql\Ddl\Column\Date;
use Zend\Db\Sql\Ddl\Column\Floating;
use Zend\Db\Sql\Ddl\Column\Integer;
use Zend\Db\Sql\Ddl\Column\Timestamp;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\Constraint\ForeignKey;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Ddl\DropTable;
use Zend\Db\Sql\Ddl\Index\Index;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\InsertMultiple;
use Zend\Db\TableGateway\TableGateway;

class Db
{
    public static $TABLES = array(
        Xhb::MAIN_TABLE,
        Account::MAIN_TABLE,
        Operation::MAIN_TABLE,
        Category::MAIN_TABLE,
        Payee::MAIN_TABLE
    );

    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $_db;

    /**
     * @var \Zend\Db\Sql\Sql
     */
    protected $_sql;

    /**
     * @var \Zend\Db\Metadata\Metadata
     */
    protected $_metadata;

    /**
     * @var array
     */
    protected $_options;

    public function __construct(Adapter $db, $options = array()) {
        $this->_db = $db;
        $this->_sql = new \Zend\Db\Sql\Sql($db);
        $this->_metadata = new \Zend\Db\Metadata\Metadata($db);
        $this->_options = $options;
    }

    /**
     * @param $xhbData
     * @param $xhbId
     * @return bool TRUE if database has been created/updated, FALSE otherwise
     */
    public function importXhbData($xhbData, $xhbId, $force = false) {
        if (!$this->xhbExists($xhbId) || $force) {
            $this->_createSchema()
                ->_importXhbData($xhbData, $xhbId);
            return true;
        }
        return false;
    }

    public function getConnection() {
        return $this->_db;
    }

    public function xhbExists($xhbId) {
        try {
            $select = $this->_sql->select(Xhb::MAIN_TABLE);
            $select->columns(array('id'))
                ->where(array('id' => $xhbId));
            $result = $this->_db->query($this->_sql->buildSqlString($select), Adapter::QUERY_MODE_EXECUTE);
        }
        catch (\RuntimeException $e) {
            return false;
        }
        return count($result) > 0;
    }

    protected function _createSchema() {
        $this->_createTables();
        return $this;
    }

    protected function _destroySchema($silent = false) {
        $this->_destroyTables($silent);
        return $this;
    }

    protected function _importXhbData($data, $xhbId) {
        try {
            // First delete existing XHB unconditionally
            $deleteStmt = new Delete(Xhb::MAIN_TABLE);
            $deleteStmt->where(array('id' => $xhbId));
            $this->_db->query($this->_sql->buildSqlString($deleteStmt), Adapter::QUERY_MODE_EXECUTE);

            $this->_insertInto(Account::MAIN_TABLE, $this->_addAdditionalFields($data['accounts'], $xhbId), null);
            $this->_insertInto(Category::MAIN_TABLE, $this->_addAdditionalFields($data['categories'], $xhbId), null);
            $this->_insertInto(Payee::MAIN_TABLE, $this->_addAdditionalFields($data['payees'], $xhbId), null);
            $this->_insertInto(Operation::MAIN_TABLE, $this->_addAdditionalFields($data['operations'], $xhbId), null);
            $this->_insertOperationSplitAmount($data['operations'], $xhbId);

            // Insert XHB row at the end, so that in case something's gone wrong before, next request may try to
            // create schema and insert data again, without risking to use invalid or incomplete data instead
            $xhbTableData = array_merge($data['properties'], array(
                'id'         => $xhbId,
                'updated_at' => time()
            ));
            $this->_insertInto(Xhb::MAIN_TABLE, array($xhbTableData));
        }
        catch (\Exception $e) {
            setlocale(LC_ALL, $oldLocale);
            // If something's gone wrong, destroy everything
            $this->_destroySchema(true);
            throw $e;
        }
        return $this;
    }

    protected function _addAdditionalFields(array &$data, $xhbId, $xhbIdColName = 'xhb_id') {
        $now = time();
        foreach($data as &$row) {
            $row[$xhbIdColName] = $xhbId;
            $row['updated_at'] = $now;
        }
        return $data;
    }

    protected function _insertOperationSplitAmount($operationData, $xhbId) {
        $data = array();
        foreach($operationData as $opData) {
            if (isset($opData['split_amount'])) {
                foreach($opData['split_amount'] as $saData) {
                    $data[] = array_merge($saData, array(
                        'xhb_id'      => $xhbId,
                        'operation_id' => $opData['id']
                    ));
                }
            }
        }
        $this->_insertInto(Operation::SPLIT_AMOUNT_TABLE, $data, null, true);
    }

    protected function _createTables() {
        $existingTables = $this->_metadata->getTableNames();

        // Xhb
        $table = Xhb::MAIN_TABLE;
        if (!in_array($table, $existingTables)) {
            $createStmt = new CreateTable($table);
            $createStmt->addColumn(new Varchar('id', 64, false))
                ->addColumn(new Varchar('title', 128))
                ->addColumn(new Integer('car_category', true))
                ->addColumn(new Integer('auto_smode', true, 0))
                ->addColumn(new Integer('auto_weekday', true, 1))
                ->addColumn(new Timestamp('updated_at'))
                ->addConstraint(new PrimaryKey('id'));
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
        }

        // Accounts
        $table = Account::MAIN_TABLE;
        if (!in_array($table, $existingTables)) {
            $createStmt = new CreateTable($table);
            $createStmt->addColumn(new Integer('xhb_id', false))
                ->addColumn(new Integer('key', false))
                ->addColumn(new Integer('pos', true))
                ->addColumn(new Integer('type', true))
                ->addColumn(new Varchar('name', 128))
                ->addColumn(new Varchar('number', 128, true))
                ->addColumn(new Varchar('bankname', 128, true))
                ->addColumn(new Floating('initial', 10, 4, false, 0))
                ->addColumn(new Floating('minimum', 10, 4, false, 0))
                ->addColumn(new Timestamp('updated_at'))
                ->addConstraint(new PrimaryKey(array('xhb_id', 'key')))
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'));
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
        }

        // Categories
        $table = Category::MAIN_TABLE;
        if (!in_array($table, $existingTables)) {
            $createStmt = new CreateTable($table);
            $createStmt->addColumn(new Integer('xhb_id', false))
                ->addColumn(new Integer('key', false))
                ->addColumn(new Integer('parent', true))
                ->addColumn(new Varchar('name', 128))
                ->addColumn(new Integer('flags', true))
                ->addColumn(new Floating('b0', 10, 4, false, 0))
                ->addColumn(new Timestamp('updated_at'))
                ->addConstraint(new PrimaryKey(array('xhb_id', 'key')))
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'));
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
        }

        // Payees
        $table = Payee::MAIN_TABLE;
        if (!in_array($table, $existingTables)) {
            $createStmt = new CreateTable($table);
            $createStmt->addColumn(new Integer('xhb_id', false))
                ->addColumn(new Integer('key', false))
                ->addColumn(new Varchar('name', 128))
                ->addColumn(new Timestamp('updated_at'))
                ->addConstraint(new PrimaryKey(array('xhb_id', 'key')))
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'));
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
        }

        // Operations
        $table = Operation::MAIN_TABLE;
        if (!in_array($table, $existingTables)) {
            $createStmt = new CreateTable($table);
            $createStmt->addColumn(new Integer('xhb_id', false))
                ->addColumn(new Integer('id', false, null, array('auto_increment' => true)))
                ->addColumn(new Date('date'))
                ->addColumn(new Integer('account', false))
                ->addColumn(new Varchar('info', 128, true))
                ->addColumn(new Floating('amount', 10, 4, false, 0))
                ->addColumn(new Integer('dst_account', true))
                ->addColumn(new Integer('paymode', true))
                ->addColumn(new Integer('st', false, 0))
                ->addColumn(new Integer('flags', true))
                ->addColumn(new Integer('payee', true))
                ->addColumn(new Integer('category', true))
                ->addColumn(new Varchar('wording', 128, true))
                ->addColumn(new Varchar('tags', 128, true))
                ->addColumn(new Varchar('scat', 128, true))
                ->addColumn(new Varchar('samt', 128, true))
                ->addColumn(new Varchar('smem', 128, true))
                ->addColumn(new Integer('kxfer', true))
                ->addColumn(new Floating('account_balance', 10, 4, false, 0))
                ->addColumn(new Floating('general_balance', 10, 4, false, 0))
                ->addColumn(new Timestamp('updated_at'))
                ->addConstraint(new PrimaryKey(array('xhb_id', 'id')))
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'));
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
        }

        // Operations - Split amount
        $table = Operation::SPLIT_AMOUNT_TABLE;
        if (!in_array($table, $existingTables)) {
            $createStmt = new CreateTable($table);
            $createStmt->addColumn(new Integer('xhb_id', false))
                ->addColumn(new Integer('operation_id', false))
                ->addColumn(new Floating('amount', 10, 4, true, 0))
                ->addColumn(new Integer('category', true))
                ->addColumn(new Varchar('wording', 128, true))
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'))
                ->addConstraint(new ForeignKey('FK_OPERATION_ID', 'operation_id', Operation::MAIN_TABLE, 'id'));
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
        }
    }

    protected function _destroyTables($silent = false) {
        foreach(self::$TABLES as $table) {
            try {
                $dropStmt = new DropTable($table);
                $this->_db->query($this->_sql->buildSqlString($dropStmt), Adapter::QUERY_MODE_EXECUTE);
            }
            catch (\Exception $e) {
                if (!$silent) {
                    throw $e;
                }
            }
        }
    }

    protected function _insertInto($table, array $data, $columns = null) {
        if (!$data) {
            return;
        }
        if ($columns === null) {
            $columns = $this->_metadata->getColumnNames($table);
        }

        $insertStmt = new InsertMultiple($table);
        if ($columns !== null) {
            $insertStmt->columns($columns);
        }
        foreach ($data as $row) {
            $insertStmt->values($row, InsertMultiple::VALUES_MERGE);
        }
        $sql = $this->_sql->buildSqlString($insertStmt);
        $this->_db->query($sql, Adapter::QUERY_MODE_EXECUTE);
    }
}