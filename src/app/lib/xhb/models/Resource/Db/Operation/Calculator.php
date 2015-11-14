<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 11/07/15
 * Time: 10:48
 */

namespace xhb\models\Resource\Db\Operation;


use xhb\models\Resource\Db\AbstractModel;
use Zend\Db\Sql\Expression;

class Calculator extends AbstractModel
{
    /**
     * @return \xhb\models\Resource\Db\Operation\Collection
     */
    public function getOperationCollection(\xhb\models\Operation\Calculator $object) {
        $operationCollection = null;
        if ($account = $object->getAccount()) {
            $operationCollection = $account->getOperationCollection();
        }
        else {
            $operationCollection = $object->getXhb()->getOperationCollection();
        }
        $operationCollection->setFlag('skip_aggregated_fields');
        return $operationCollection;
    }

    /**
     * @param \xhb\models\Operation\Calculator $object
     * @param string $type
     * @param int $referenceTime
     * @return float
     */
    public function getCurrentBalance(\xhb\models\Operation\Calculator $object, $type, $referenceTime) {
        $txnTypeFilter = $object->getBalanceStatuses($type);
        $operationCollection = $this->getOperationCollection($object)
            ->addFieldToSelect(array('balance' => new Expression('SUM(amount)')))
            ->addFieldToFilter('st', array('in' => $txnTypeFilter))
            ->addFieldToFilter('date', array('le' => $referenceTime))
            ->setLimit(1);
        $item = $operationCollection->getFirstItem();
        if ($item) {
            return $item->getBalance();
        }
        return 0.0;
    }
}