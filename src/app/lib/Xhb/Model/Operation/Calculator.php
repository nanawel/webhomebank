<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 11/07/15
 * Time: 10:48
 */

namespace Xhb\Model\Operation;

use Xhb\Model\Constants;
use Xhb\Model\Operation;
use Xhb\Model\Xhb;
use Xhb\Model\XhbModel;
use Xhb\Util\Date;

/**
 * Class Calculator
 *
 * @method \Xhb\Model\Resource\Db\Operation\Calculator getResource()
 *
 * @package Xhb\Model\Operation
 */
class Calculator extends XhbModel
{
    const DATE_FORMAT = 'Y-m-d';

    /**
     * @var \Xhb\Model\Account
     */
    protected $_account;

    /**
     * @var array
     */
    protected $_cache = [];

    public function __construct(Xhb $xhb, $account = null, array $data = []) {
        parent::__construct($data);
        $this->setXhb($xhb);
        if (is_numeric($account)) {
            $account = $xhb->getAccount($account);
        }

        $this->_account = $account;
    }

    /**
     * @return \Xhb\Model\Operation\Collection
     */
    public function getOperationCollection() {
        return $this->getResource()->getOperationCollection($this);
    }

    /**
     * @return \Xhb\Model\Account
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

    public function getCurrentBalance(string $type, $force = false, $referenceDate = 'now') {
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

    public function getFutureDate($referenceDate = 'now'): \DateTime {
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
    public function getBalanceByDate($period): array {
        $balance = [];
        $it = $this->getOperationCollection()->getIterator();

        $currentOperation = $it->current();
        $firstKey = current($period)->format(self::DATE_FORMAT);
        $currentBalance = $this->getAccount()->getInitial();
        $previousBalance = $currentBalance;
        foreach ($period as $date) {
            $arrayKey = $date->format(self::DATE_FORMAT);
            /* @var $date \DateTime */
            while ($currentOperation->getDateModel() <= $date) {
                $currentBalance = $currentOperation->getAccountBalance();
                $balance[$arrayKey] = [
                    'date'    => $date,
                    'balance' => $currentBalance,
                ];

                // Set initial balance if needed
                if ($balance[$firstKey] === null && $currentOperation->getDateModel() >= $date) {
                    $balance[$firstKey] = [
                        'date'    => $date,
                        'balance' => $previousBalance,
                    ];
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
                $balance[$arrayKey] = [
                    'date'    => $date,
                    'balance' => $currentBalance,
                ];
            }
        }

        return $balance;
    }

    /**
     *
     * @return int[]
     */
    public static function getReconciliedStatuses(): array {
        return [Constants::TXN_STATUS_RECONCILED];
    }

    /**
     *
     * @return int[]
     */
    public static function getUnreconciliedStatuses(): array {
        return array_diff(Constants::$TXN_STATUS, [Constants::TXN_STATUS_RECONCILED, Constants::TXN_STATUS_VOID]);
    }

    /**
     *
     * @return int[]
     */
    public static function getClearedStatuses(): array {
        return [Constants::TXN_STATUS_CLEARED];
    }

    /**
     *
     * @return int[]
     */
    public static function getUnclearedStatuses(): array {
        return array_diff(Constants::$TXN_STATUS, [Constants::TXN_STATUS_CLEARED, Constants::TXN_STATUS_VOID]);
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
    public static function getBalanceTodayStatuses(): array {
        return array_diff(Constants::$TXN_STATUS, [Constants::TXN_STATUS_REMIND, Constants::TXN_STATUS_VOID]);
    }

    /**
     *
     * @return int[]
     */
    public static function getBalanceFutureStatuses(): array {
        return array_diff(Constants::$TXN_STATUS, [Constants::TXN_STATUS_REMIND, Constants::TXN_STATUS_VOID]);
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
