<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 09/11/15
 * Time: 17:09
 */

namespace app\helpers\whb\Chart;

use app\helpers\core\Output;
use app\helpers\whb\AccountOperation;
use app\helpers\whb\Chart;
use app\models\core\I18n;
use Xhb\Model\Constants;
use Xhb\Model\Operation\Calculator;
use Xhb\Model\Xhb;
use Xhb\Util\Date;

class Operation
{
    /**
     * @param Collection $collection
     * @param array $collectionFilters
     * @return array
     */
    public static function getTopSpendingReportData(Xhb $xhb, array $collectionFilters) {
        $i18n = I18n::instance();
        $opColl = $xhb->getOperationCollection();
        AccountOperation::applyFiltersOnCollection($opColl, $collectionFilters);
        $opColl->addFieldToFilter('paymode', array('neq' => Constants::PAYMODE_INTXFER))
            ->addFieldToFilter('amount', array('lt' => 0))
            ->addFieldToFilter('date', array('lt' => Date::dateToJd(Date::getDate())));

        // FIXME does not handle split amounts yet
        $maxResults = 6;
        $sumByCategory = Chart::sumBy($opColl, 'category', 'amount', 0, $maxResults);

        $return = array();
        $n = 0;
        foreach($sumByCategory as $catId => $sum) {
            $cat = $xhb->getCategory($catId);
            if (!$cat) {
                $catName = $i18n->tr($n == ($maxResults - 1) ? 'Other' : '(Unknown)');
            }
            else {
                $catName = $cat->getFullname();
            }
            $v = abs(round($sum, 2));
            $return[] = array(
                'value'          => $v,
                'label'          => $i18n->tr('{0} ({1})', $catName, I18n::instance()->currency($v)),
                'color'          => Output::rgbToCss(Chart::getColor($n++))
            );
        }
        return $return;
    }

    /**
     * @param Collection $collection
     * @param array $collectionFilters
     * @param array $accountIds
     * @return array
     */
    public static function getBalanceReportData(Xhb $xhb, array $collectionFilters, array $accountIds, $withGrandTotal = false) {
        $return = array(
            'labels'   => array(),
            'datasets' => array()
        );
        $operationCollection = $xhb->getOperationCollection();
        $processedFilters = AccountOperation::applyFiltersOnCollection($operationCollection, $collectionFilters);
        $firstOp = $operationCollection->getFirstItem();
        if (!$firstOp) {
            return $return;
        }

        $startDate = isset($processedFilters['start_date']) ? $processedFilters['start_date'] : $firstOp->getDateModel();
        $endDate = isset($processedFilters['end_date']) ? $processedFilters['end_date'] : new \DateTime('last day of this month');
        $datePeriod = self::getDatePeriod($xhb, $startDate, $endDate);

        $rawBalanceData = array();
        foreach($accountIds as $accountId) {
            $calculator = new Calculator($xhb, $accountId);
            $rawBalanceData[$accountId] = $calculator->getBalanceByDate($datePeriod);
        }

        $now = new \DateTime();
        $idx = 0;
        $addLabels = true;
        $grandTotal = array();
        foreach($rawBalanceData as $accountId => $accountBalanceData) {
            $periodIdx = 0;
            if (!isset($grandTotal[$idx])) {
                $grandTotal[$idx] = array();
            }
            $return['datasets'][$idx] = array(
                'label'                => $xhb->getAccount($accountId)->getName(),
                'strokeColor'          => Output::rgbToCss(Chart::getColor($idx)),
                'pointColor'           => Output::rgbToCss(Chart::getColor($idx)),
                'pointHighlightFill'   => '#fff',
                'pointHighlightStroke' => '#bbb',
                'data'                 => array()
            );
            foreach($accountBalanceData as $periodBalance) {
                if ($addLabels) {
                    $return['labels'][] = I18n::instance()->date($periodBalance['date']);
                }
                if ($periodBalance['date'] < $now) {
                    $return['datasets'][$idx]['data'][$periodIdx] = array(
                        'x' => $periodBalance['date']->getTimestamp(),
                        'y' => $periodBalance['balance']
                    );
                    $grandTotal[$periodIdx][] = array(
                        'x' => $periodBalance['date'],
                        'y' => $periodBalance['balance']
                    );
                }
                else {
                    $return['datasets'][$idx]['data'][$periodIdx] = array(
                        'x' => $periodBalance['date']->getTimestamp(),
                        //'y' => null
                    );
                    $grandTotal[$periodIdx][] = array(
                        'x' => $periodBalance['date'],
                        //'y' => null
                    );
                }
                $periodIdx++;
            }

            $addLabels = false;
            $idx++;
        }
        if ($withGrandTotal) {
            $return['datasets'][$idx] = array(
                'label'                => I18n::instance()->tr('Grand Total'),
                'strokeColor'          => Output::rgbToCss(array(0, 0, 0)),
                'pointColor'           => Output::rgbToCss(array(0, 0, 0)),
                'pointHighlightFill'   => '#fff',
                'pointHighlightStroke' => '#bbb',
                'data'                 => array()
            );
            $periodIdx = 0;
            foreach($grandTotal as $periodAccountsBalance) {
                $balance = array_sum(array_column($periodAccountsBalance, 'y'));
                $date = current($periodAccountsBalance)['x'];
                $balanceData = array(
                    'x' => $date->getTimestamp()
                );
                if ($date < $now) {
                    $balanceData['y'] = $balance;
                }
                $return['datasets'][$idx]['data'][$periodIdx] = $balanceData;
                $periodIdx++;
            }
        }
        return $return;
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return \DatePeriod
     */
    public static function getDatePeriod(Xhb $xhb, \DateTime $startDate, \DateTime $endDate, \DateInterval $interval = null) {
        return $xhb->getDateHelper()->getDatePeriod($startDate, $endDate, $interval);
    }
}
