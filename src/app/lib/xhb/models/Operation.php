<?php
namespace xhb\models;

use xhb\util\Date;

/**
 * Class Operation
 *
 * @method float getAmount()
 * @method int getPaymode()
 * @method int getPayee()
 * @method int getCategory()
 * @method string getInfo()
 * @method string getFlags()
 * @method int getSt()
 * @method float getAccountBalance()
 * @method float getGeneralBalance()
 * @method string getMemo()
 *
 * @package xhb\models
 */
class Operation extends XhbModel
{
    /**
     * @var \DateTime
     */
    protected $_date = null;

    /**
     * This property is only used to speed up getter calls
     *
     * @var int Account ID
     */
    protected $_account = null;

    /**
     * @var array
     */
    protected $_splitAmountData = null;

    protected function _init(array $data) {
        parent::_init($data);
        if (isset($data['account'])) {
            $this->_account = $data['account'];
        }
    }

    /**
     * @return \DateTime
     */
    public function getDateModel() {
        if (!$this->_date) {
            $this->_date = Date::jdToDate($this->getDate());
        }
        return clone $this->_date;
    }

    /**
     * @return int
     */
    public function getAccount() {
        return $this->_account;
    }

    /**
     * Alias for getSt()
     *
     * @return int
     */
    public function getStatus() {
        return $this->getSt();
    }

    public function getAccountModel() {
        return $this->getXhb()->getAccount($this->getAccount());
    }

    public function getPayeeModel() {
        return $this->getXhb()->getPayee($this->getPayee());
    }

    public function getCategoryModel() {
        return $this->getXhb()->getCategory($this->getCategory());
    }

    /**
     * @return array
     */
    public function getScat() {
        if (is_string($this->getData('scat'))) {
            return explode('||', $this->getData('scat'));
        }
        return array();
    }

    /**
     * @return array
     */
    public function getSamt() {
        if (is_string($this->getData('samt'))) {
            return explode('||', $this->getData('samt'));
        }
        return array();
    }

    /**
     * @return array
     */
    public function getSmem() {
        if (is_string($this->getData('smem'))) {
            return explode('||', $this->getData('smem'));
        }
        return array();
    }

    /**
     * @return array
     */
    public function getSplitAmount() {
        if (!$this->_splitAmountData) {
            $scat = $this->getScat();
            $samt = $this->getSamt();
            $smem = $this->getSmem();

            $splitAmountData = array();
            foreach($scat as $k => $cat) {
                $splitAmountData[$k] = array(
                    'category' => $cat,
                    'amount'   => isset($samt[$k]) ? $samt[$k] : null,
                    'wording'  => isset($smem[$k]) ? $smem[$k] : null
                );
            }
            $this->_splitAmountData = $splitAmountData;
        }
        return $this->_splitAmountData;
    }

    public function getPaymodeCode() {
        if ($paymode = $this->getPaymode()) {
            return array_search($paymode, Constants::$PAYMODES);
        }
        return null;
    }

    /**
     * @return Category[]
     */
    public function getCategoryModels() {
        if ($cat = $this->getCategoryModel()) {
            return array($cat);
        }
        else if ($catIds = $this->getScat()) {
            $categories = array();
            foreach($catIds as $catId) {
                if ($catId > 0) {    // 0 is used for non-categorized amounts
                    $categories[] = $this->getXhb()->getCategory($catId);
                }
            }
            return $categories;
        }
        return null;
    }
}