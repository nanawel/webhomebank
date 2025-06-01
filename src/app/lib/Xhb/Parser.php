<?php
namespace Xhb;

use SimpleXMLElement;
use Xhb\Model\Constants;

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

    public function __construct($file = null, protected $_params = []) {
        if (!is_file($file) || !is_readable($file)) {
            throw new \InvalidArgumentException(sprintf("File '%s' does not exist or is not readable.", $file));
        }

        $this->setFile($file);
    }

    public function setFile($file): void {
        $this->_xhbFile = realpath($file);
    }

    public function parse($force = false): void {
        if ($this->_data === null || $force) {
            $xml = new SimpleXMLElement(file_get_contents($this->_xhbFile));

            $this->_data = [];

            $nodes = [
                'properties'  => 'properties',
                'accounts'    => 'account',
                'payees'      => 'pay',
                'categories'  => 'cat',
                'favorites'   => 'fav',
                'operations'  => 'ope'
            ];
            foreach($nodes as $name => $index) {
                $this->_data[$name] = [];
                $id = 1;
                foreach ($xml->$index as $subnode) {
                    // Special behavior for properties, there can be only one row
                    if ($name === 'properties') {
                        $this->_data[$name] = self::nodeAttributesToArray($subnode);
                        continue;
                    }

                    if ($subnode['key']) {
                        $this->_data[$name][(string) $subnode['key']] = self::nodeAttributesToArray($subnode);
                    }
                    else {
                        $data = array_merge(self::nodeAttributesToArray($subnode), [self::GENERATED_ID_NAME => $id]);
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
            if (!isset($cat['b0'])) {
                $cat['b0'] = 0;
            }
        }

        foreach($this->_data['operations'] as &$op) {
            if (isset($op['samt'])) {
                $op['split_amount'] = $this->_getCategorySplitAmountData($op);
            }

            if (!isset($op['st'])) {
                $op['st'] = 0;
            }
        }
    }

    protected function _addCalculatedFields() {
        // Add general and account balances after each operation
        $generalBalance = 0;
        $accountBalances = [];
        foreach($this->_data['accounts'] as $account) {
            $generalBalance += $account['initial'];
            $accountBalances[$account['key']] = $account['initial'];
        }

        foreach($this->_data['operations'] as &$operation) {
            if ($operation['st'] != Constants::TXN_STATUS_VOID) {
                $generalBalance += $operation['amount'];
                $accountBalances[$operation['account']] += $operation['amount'];
            }

            $operation['general_balance'] = $generalBalance;
            $operation['account_balance'] = $accountBalances[$operation['account']];
        }

        // More?
    }

    /**
     * @return list<array{amount: string, category: string, wording: string}>
     */
    protected function _getCategorySplitAmountData(array $opData): array {
        $amounts = explode('||', $opData['samt']);
        $categories = explode('||', $opData['scat']);
        $memos = explode('||', $opData['smem']);

        if (count($amounts) !== count($categories) || count($categories) !== count($memos)) {
            throw new \Exception('Invalid split amount data on operation.');
        }

        $splitAmountData = [];
        $counter = count($amounts);
        for ($i = 0; $i < $counter; $i++) {
            $splitAmountData[] = [
                'amount'   => $amounts[$i],
                'category' => $categories[$i],
                'wording'  => $memos[$i]
            ];
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

    /**
     * @return string[]
     */
    protected static function nodeAttributesToArray(SimpleXMLElement $xml): array {
        $array = [];
        foreach ($xml->attributes() as $k => $v) {
            $array[$k] = (string) $v;
        }

        return $array;
    }
}