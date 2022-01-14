<?php

namespace Xhb\Adapter;

use app\models\core\Log;
use DB\SQL;
use Laminas\Db\Sql\Ddl\Column\Text;
use Xhb\Model\Resource\Db\Account;
use Xhb\Model\Resource\Db\Category;
use Xhb\Model\Resource\Db\Operation;
use Xhb\Model\Resource\Db\Payee;
use Xhb\Model\Resource\Db\Xhb;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Metadata;
use Laminas\Db\Sql\Ddl\Column\Column;
use Laminas\Db\Sql\Ddl\Column\Date;
use Laminas\Db\Sql\Ddl\Column\Floating;
use Laminas\Db\Sql\Ddl\Column\Integer;
use Laminas\Db\Sql\Ddl\Column\Timestamp;
use Laminas\Db\Sql\Ddl\Column\Varchar;
use Laminas\Db\Sql\Ddl\Constraint\ForeignKey;
use Laminas\Db\Sql\Ddl\Constraint\PrimaryKey;
use Laminas\Db\Sql\Ddl\CreateTable;
use Laminas\Db\Sql\Ddl\DropTable;
use Laminas\Db\Sql\Ddl\Index\Index;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\InsertMultiple;
use Laminas\Db\TableGateway\TableGateway;

class Db implements AdapterInterface
{
    public static $TABLES = array(
        Xhb::MAIN_TABLE,
        Account::MAIN_TABLE,
        Operation::MAIN_TABLE,
        Category::MAIN_TABLE,
        Payee::MAIN_TABLE
    );

    /**
     * @var \Laminas\Db\Adapter\Adapter
     */
    protected $_db;

    /**
     * @var \Laminas\Db\Sql\Sql
     */
    protected $_sql;

    /**
     * @var \Laminas\Db\Metadata\Metadata
     */
    protected $_metadata;

    /**
     * @var array
     */
    protected $_config;

    public function __construct(array $config) {
        if (!isset($config['db'])) {
            throw new \Exception('Missing DB config');
        }
        $this->_db = new Adapter($config['db']);
        $this->_sql = new \Laminas\Db\Sql\Sql($this->_db);
        $this->_metadata = new \Laminas\Db\Metadata\Metadata($this->_db);
        $this->_config = $config;
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

            $operationsData = $this->_prepareOperationsData($data, $xhbId);
            $this->_insertInto(Operation::MAIN_TABLE, $operationsData, null);
            $this->_insertOperationSplitAmount($operationsData, $xhbId);

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

    protected function _prepareOperationsData($xhbData, $xhbId) {
        $recordsByKey = [];
        foreach ($xhbData as $type => $records) {
            if (is_array($records)) {
                foreach ($records as $record) {
                    if (!empty($record['key'])) {
                        $recordsByKey[$type][$record['key']] = $record;
                    }
                }
            }
        }

        foreach ($xhbData['operations'] as &$operation) {
            $searchText = [
                $operation['info'],
                $operation['wording'],
                $operation['smem'],
                $operation['tags'],
            ];
            if (!empty($operation['category'])) {
                $searchText[] = $recordsByKey['categories'][$operation['category']]['name'];
            }
            if (!empty($operation['payee'])) {
                $searchText[] = $recordsByKey['payees'][$operation['payee']]['name'];
            }
            if (!empty($operation['scat'])) {
                foreach (explode('||', $operation['scat']) as $categoryKey) {
                    $searchText[] = $recordsByKey['categories'][$categoryKey]['name'];
                }
            }

            $operation['text_search'] = implode('|', array_filter($searchText));
        }

        return $this->_addAdditionalFields($xhbData['operations'], $xhbId);
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
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'))
            ;
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
            $this->_createIndexes($table, ['type', 'name', 'number', 'bankname', 'updated_at']);
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
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'))
            ;
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
            $this->_createIndexes($table, ['parent', 'name', 'flags', 'updated_at']);
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
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'))
            ;
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
            $this->_createIndexes($table, ['name', 'updated_at']);
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
                ->addColumn(new Text('text_search'))
                ->addConstraint(new PrimaryKey(array('xhb_id', 'id')))
                ->addConstraint(new ForeignKey('FK_XHB_ID', 'xhb_id', Xhb::MAIN_TABLE, 'id', 'CASCADE', 'CASCADE'))
            ;
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
            $this->_createIndexes($table, [
                'date', 'account', 'info', 'amount', 'dst_account', 'st',
                'flags', 'wording', 'tags', 'scat', 'smem', 'updated_at'
            ]);
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
                ->addConstraint(new ForeignKey('FK_OPERATION_ID', 'operation_id', Operation::MAIN_TABLE, 'id'))
            ;
            $this->_db->query($this->_sql->buildSqlString($createStmt), Adapter::QUERY_MODE_EXECUTE);
            $this->_createIndexes($table, ['amount', 'category', 'wording']);
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

    protected function _createIndexes($table, array $columns) {
        foreach ($columns as $column) {
            $this->_db->query(sprintf(
                'CREATE INDEX %s ON %s(%s)',
                "{$table}_{$column}_IDX",
                $table,
                is_array($column) ? implode(', ', $column) : $column
            ), Adapter::QUERY_MODE_EXECUTE);
        }
    }
}
