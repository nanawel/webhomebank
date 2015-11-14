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
use xhb\models\Constants;
use xhb\models\Operation\Calculator;
use xhb\models\Xhb;

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
        $opColl->addFieldToFilter('paymode', array('neq' => Constants::PAYMODE_INTXFER));

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
    public static function getBalanceReportData(Xhb $xhb, array $collectionFilters, array $accountIds) {
        $return = array(
            'labels'   => array(),
            'datasets' => array()
        );
        $operationCollection = $xhb->getOperationCollection()
            ->setFlag('skip_aggregated_fields', true);
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
        foreach($rawBalanceData as $accountId => $accountBalanceData) {
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
                    $return['datasets'][$idx]['data'][] = array(
                        'x' => $periodBalance['date']->getTimestamp(),
                        'y' => $periodBalance['balance']
                    );
                }
                else {
                    $return['datasets'][$idx]['data'][] = array(
                        'x' => $periodBalance['date']->getTimestamp(),
                        //'y' => null
                    );
                }
            }
            $addLabels = false;
            $idx++;
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