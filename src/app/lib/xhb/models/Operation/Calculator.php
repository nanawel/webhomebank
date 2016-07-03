<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 11/07/15
 * Time: 10:48
 */

namespace xhb\models\Operation;

use xhb\models\Constants;
use xhb\models\Operation;
use xhb\models\Xhb;
use xhb\models\XhbModel;
use xhb\util\Date;

/**
 * Class Calculator
 *
 * @method \xhb\models\Resource\Db\Operation\Calculator getResource()
 *
 * @package xhb\models\Operation
 */
class Calculator extends XhbModel
{
    const DATE_FORMAT = 'Y-m-d';

    /**
     * @var \xhb\models\Account
     */
    protected $_account;

    /**
     * @var array
     */
    protected $_cache = array();

    public function __construct(Xhb $xhb, $account = null, array $data = array()) {
        parent::__construct($data);
        $this->setXhb($xhb);
        if (is_numeric($account)) {
            $account = $xhb->getAccount($account);
        }
        $this->_account = $account;
    }

    /**
     * @return \xhb\models\Operation\Collection
     */
    public function getOperationCollection() {
        return $this->getResource()->getOperationCollection($this);
    }

    /**
     * @return \xhb\models\Account
     */
    public function getAccount() {
        return $this->_account;
    }

    public function getBankBalance() {
        return $this->getCurrentBalance(Constants::BALANCE_TYPE_BANK);
    }

    public function getTodayBalance() {
        return $this->getCurrentBalance(Constants::BALANCE_TYPE_TODAY);
    }

    public function getFutureBalance() {
        return $this->getCurrentBalance(Constants::BALANCE_TYPE_FUTURE);
    }

    public function getCurrentBalance($type, $force = false, $referenceDate = 'now') {
        if ($force || !isset($this->_cache['balance_' . $type])) {
//            $txnTypeFilter = self::getBalanceStatuses($type);
            $referenceTime = Date::dateToJd($this->getBalanceReferenceTime($type, $referenceDate));
//            $operationCollection = $this->getOperationCollection()
//                ->addFieldToFilter('st', array('in' => $txnTypeFilter))
//                ->addFieldToFilter('date', array('le' => $referenceTime));
//
//            foreach($operationCollection as $o) {
//                $balance += $o->getAmount();
//            }
            $balance = (float) $this->_getInitialAmount();
            $balance += $this->getResource()->getCurrentBalance($this, $type, $referenceTime);
            $this->_cache['balance_' . $type] = $balance;
        }
        return $this->_cache['balance_' . $type];
    }

    public function getFutureDate($referenceDate = 'now') {
        $d = new \DateTime($referenceDate);
        $d->setTimestamp(mktime(0, 0, 0, $d->format('m') + 1, $this->getXhb()->getAutoWeekday()));
        return $d;
    }

    public function _getInitialAmount() {
        if ($account = $this->getAccount()) {
            return $account->getInitial();
        }
        $generalInitial = 0.0;
        foreach($this->getXhb()->getAccountCollection() as $account) {
            $generalInitial += $account->getInitial();
        }
        return $generalInitial;
    }

    /**
     * @param \DatePeriod $period
     * @return array
     */
    public function getBalanceByDate($period) {
        $balance = array();
        $it = $this->getOperationCollection()->getIterator();

        $currentOperation = $it->current();
        $firstKey = current($period)->format(self::DATE_FORMAT);
        $currentBalance = $previousBalance = $this->getAccount()->getInitial();
        foreach ($period as $date) {
            $arrayKey = $date->format(self::DATE_FORMAT);
            /* @var $date \DateTime */
            while ($currentOperation->getDateModel() <= $date) {
                $currentBalance = $currentOperation->getAccountBalance();
                $balance[$arrayKey] = array(
                    'date'    => $date,
                    'balance' => $currentBalance,
                );

                // Set initial balance if needed
                if ($balance[$firstKey] === null && $currentOperation->getDateModel() >= $date) {
                    $balance[$firstKey] = array(
                        'date'    => $date,
                        'balance' => $previousBalance,
                    );
                }
                $previousBalance = $currentOperation->getAccountBalance();

                $it->next();
                if (!$it->valid()) {
                    break;
                }
                $currentOperation = $it->current();
            }

            // Report current balance on dates with no operations
            if (!isset($balance[$arrayKey])) {
                $balance[$arrayKey] = array(
                    'date'    => $date,
                    'balance' => $currentBalance,
                );
            }
        }
        return $balance;
    }

    /**
     *
     * @return int[]
     */
    public static function getReconciliedStatuses() {
        return array(Constants::TXN_STATUS_RECONCILED);
    }

    /**
     *
     * @return int[]
     */
    public static function getUnreconciliedStatuses() {
        return array_diff(Constants::$TXN_STATUS, array(Constants::TXN_STATUS_RECONCILED));
    }

    /**
     *
     * @return int[]
     */
    public static function getClearedStatuses() {
        return array(Constants::TXN_STATUS_CLEARED);
    }

    /**
     *
     * @return int[]
     */
    public static function getUnclearedStatuses() {
        return array_diff(Constants::$TXN_STATUS, array(Constants::TXN_STATUS_CLEARED));
    }

    /**
     *
     * @return int[]
     */
    public static function getBalanceBankStatuses() {
        return self::getReconciliedStatuses();
    }

    /**
     *
     * @return int[]
     */
    public static function getBalanceTodayStatuses() {
        return array_diff(Constants::$TXN_STATUS, array(Constants::TXN_STATUS_REMIND));
    }

    /**
     *
     * @return int[]
     */
    public static function getBalanceFutureStatuses() {
        return array_diff(Constants::$TXN_STATUS, array(Constants::TXN_STATUS_REMIND));
    }

    /**
     *
     * @return int[]
     */
    public function getBalanceStatuses($type) {
        $statuses = null;
        switch($type) {
            case Constants::BALANCE_TYPE_BANK:
                $statuses = self::getReconciliedStatuses();
                break;

            case Constants::BALANCE_TYPE_TODAY:
                $statuses = self::getBalanceTodayStatuses();
                break;

            case Constants::BALANCE_TYPE_FUTURE:
                $statuses = self::getBalanceFutureStatuses();
        }
        return $statuses;
    }

    public function getBalanceReferenceTime($type, $referenceDate = 'now') {
        $referenceTime = null;
        switch($type) {
            case Constants::BALANCE_TYPE_FUTURE:
                $referenceTime = $this->getFutureDate($referenceDate);
                break;

            case Constants::BALANCE_TYPE_BANK:
            case Constants::BALANCE_TYPE_TODAY:
            default:
                $referenceTime = new \DateTime($referenceDate);
                break;
        }
        $referenceTime->setTime(23, 59, 59);
        return $referenceTime;
    }
} 