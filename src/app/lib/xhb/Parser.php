<?php
namespace xhb;
use SimpleXMLElement as SimpleXMLElement;


/**
 * Read XHB files and return data as arrays
 *
 * @author Anael
 * @package xhb
 */
class Parser
{
    const GENERATED_ID_NAME = 'id';

    /**
     * @var string
     */
    protected $_xhbFile = null;

    protected $_params = array();

    /**
     * XHB file ID
     *
     * @var string
     */
    protected $_id = null;

    /**
     * @var array
     */
    protected $_data = null;

    public function __construct($file = null, $params = array()) {
        if (!is_file($file) || !is_readable($file)) {
            throw new \InvalidArgumentException("File '$file' does not exist or is not readable.");
        }
        $this->setFile($file);
        $this->_params = $params;
    }

    public function setFile($file) {
        $this->_xhbFile = realpath($file);
    }

    public function parse($force = false) {
        if ($this->_data === null || $force) {
            $xml = new SimpleXMLElement(file_get_contents($this->_xhbFile));

            $this->_data = array();

            $nodes = array(
                'properties'  => 'properties',
                'accounts'    => 'account',
                'payees'      => 'pay',
                'categories'  => 'cat',
                'favorites'   => 'fav',
                'operations'  => 'ope'
            );
            foreach($nodes as $name => $index) {
                $this->_data[$name] = array();
                $id = 1;
                foreach ($xml->$index as $subnode) {
                    // Special behavior for properties, there can be only one row
                    if ($name == 'properties') {
                        $this->_data[$name] = self::nodeAttributesToArray($subnode);
                        continue;
                    }
                    if ($subnode['key']) {
                        $this->_data[$name][(string) $subnode['key']] = self::nodeAttributesToArray($subnode);
                    }
                    else {
                        $data = array_merge(self::nodeAttributesToArray($subnode), array(self::GENERATED_ID_NAME => $id));
                        $this->_data[$name][$id] = $data;
                        $id++;
                    }
                }
            }
            $this->_filterData();
            $this->_addCalculatedFields();
            $this->_id = sha1(serialize($this->_data));
        }
    }

    public function getXhbData() {
        $this->parse();
        return $this->_data;
    }

    public function getUniqueKey() {
        if (!$this->_id) {
            $this->_id = sha1_file($this->_xhbFile);
        }
        return $this->_id;
    }

    protected function _filterData() {
        foreach($this->_data['categories'] as &$cat) {
            if (!isset($cat['b0'])) $cat['b0'] = 0;
        }
        foreach($this->_data['operations'] as &$op) {
            if (isset($op['samt'])) {
                $op['split_amount'] = $this->_getCategorySplitAmountData($op);
            }
            if (!isset($op['st'])) $op['st'] = 0;
        }
    }

    protected function _addCalculatedFields() {
        // Add general and account balances after each operation
        $generalBalance = 0;
        $accountBalances = array();
        foreach($this->_data['accounts'] as $account) {
            $generalBalance += $account['initial'];
            $accountBalances[$account['key']] = $account['initial'];
        }
        foreach($this->_data['operations'] as &$operation) {
            $generalBalance += $operation['amount'];
            $accountBalances[$operation['account']] += $operation['amount'];

            $operation['general_balance'] = $generalBalance;
            $operation['account_balance'] = $accountBalances[$operation['account']];
        }

        // More?
    }

    protected function _getCategorySplitAmountData(array $opData) {
        $amounts = explode('||', $opData['samt']);
        $categories = explode('||', $opData['scat']);
        $memos = explode('||', $opData['smem']);

        if (count($amounts) != count($categories) || count($categories) != count($memos)) {
            throw new \Exception('Invalid split amount data on operation.');
        }
        $splitAmountData = array();
        for ($i = 0; $i < count($amounts); $i++) {
            $splitAmountData[] = array(
                'amount'   => $amounts[$i],
                'category' => $categories[$i],
                'wording'  => $memos[$i]
            );
        }
        return $splitAmountData;
    }

    public function getAccountsData() {
        $this->parse();
        return $this->_data['accounts'];
    }

    public function getPayeesData() {
        $this->parse();
        return $this->_data['payees'];
    }

    public function getCategoriesData() {
        $this->parse();
        return $this->_data['categories'];
    }

    public function getFavoritesData() {
        $this->parse();
        return $this->_data['favorites'];
    }

    public function getOperationsData() {
        $this->parse();
        return $this->_data['operations'];
    }

    protected static function nodeAttributesToArray(SimpleXMLElement $xml) {
        $array = array();
        foreach ($xml->attributes() as $k => $v) {
            $array[$k] = (string) $v;
        }
        return $array;
    }
}