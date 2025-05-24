<?php
namespace Xhb\Model;

use Xhb\Model\Operation\Calculator;

/**
 * Class Account
 *
 * @method int getKey()
 * @method string getName()
 * @method string getNumber()
 * @method string getBankname()
 * @method float getInitial()
 * @method int getPost()
 * @method float getMinimum()
 *
 * @package Xhb\Model
 */
class Account extends XhbModel
{
    protected $_calculator;

    public function __construct(array $data) {
        parent::__construct($data);
    }

    public function getId() {
        return $this->getKey();
    }

    public function getType($returnLabel = false) {
        $typeId = parent::getType();
        if (!$returnLabel) {
            return $typeId;
        }

        return array_search($typeId, Constants::$ACC_TYPE, true);
    }

    /**
     * @return Operation\Collection
     */
    public function getOperationCollection() {
        $operations = $this->getXhb()->getOperationCollection();
        $operations->addFieldToFilter('account', $this->getId());
        return $operations;
    }

    protected function _getCalculator() {
        if (!$this->_calculator) {
            $this->_calculator = new Calculator($this->getXhb(), $this);
        }

        return $this->_calculator;
    }

    public function getBankBalance() {
        return $this->_getCalculator()->getCurrentBalance(Constants::BALANCE_TYPE_BANK);
    }

    public function getTodayBalance() {
        return $this->_getCalculator()->getCurrentBalance(Constants::BALANCE_TYPE_TODAY);
    }

    public function getFutureBalance() {
        return $this->_getCalculator()->getCurrentBalance(Constants::BALANCE_TYPE_FUTURE);
    }
}
