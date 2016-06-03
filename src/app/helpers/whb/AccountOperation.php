<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 14/07/15
 * Time: 18:42
 */

namespace app\helpers\whb;

use xhb\models\Constants;
use xhb\models\Operation;
use xhb\models\Resource\AbstractCollection;
use xhb\models\Xhb\DateHelper;
use xhb\util\Date;

class AccountOperation
{
    /**
     *
     * @return array
     */
    public static function getStaticCollectionFilters() {
        $return = array(
            'period' => array(
                'label' => 'Range',
                'values' => array(
                    DateHelper::TIME_PERIOD_THIS_MONTH      => 'This Month',
                    DateHelper::TIME_PERIOD_LAST_MONTH      => 'Last Month',
                    DateHelper::TIME_PERIOD_THIS_QUARTER    => 'This Quarter',
                    DateHelper::TIME_PERIOD_LAST_QUARTER    => 'Last Quarter',
                    DateHelper::TIME_PERIOD_THIS_YEAR       => 'This Year',
                    DateHelper::TIME_PERIOD_LAST_YEAR       => 'Last Year',
                    '-sep1-'                          => '-',
                    DateHelper::TIME_PERIOD_LAST_30D        => 'Last 30 Days',
                    DateHelper::TIME_PERIOD_LAST_60D        => 'Last 60 Days',
                    DateHelper::TIME_PERIOD_LAST_90D        => 'Last 90 Days',
                    DateHelper::TIME_PERIOD_LAST_120D       => 'Last 120 Days',
                    '-sep2-'                          => '-',
                    //TODO Custom range
                    DateHelper::TIME_PERIOD_ALL_DATE        => 'All Date'
                )
            ),
            'type' => array(
                'label' => 'Type',
                'values' => array(
                    'any_type'  => 'Any Type',
                    '--------'  => '-',
                    'outcome'   => 'Outcome',
                    'income'    => 'Income'
                )
            ),
            'status' => array(
                'label' => 'Status',
                'values' => array(
                    'any_status'     => 'Any Status',
                    '--------'       => '-',
                    'uncategorized'  => 'Uncategorized',
                    'unreconciled'   => 'Unreconciled',
                    'uncleared'      => 'Uncleared',
                    'reconciled'     => 'Reconciled',
                    'cleared'        => 'Cleared'
                )
            )
        );
        return $return;
    }

    /**
     * @param AbstractCollection $collection
     * @param $filters
     * @return array
     */
    public static function applyFiltersOnCollection(AbstractCollection $collection, $filters) {
        $processedFilters = array();
        $xhb = $collection->getXhb();
        foreach($filters as $name => $value) {
            switch ($name) {
                case 'period':
                    $periods = $xhb->getDateHelper()->getPredefinedTimePeriods();
                    $period = isset($periods[$filters['period']])
                        ? $periods[$filters['period']]
                        : $periods[DateHelper::TIME_PERIOD_DEFAULT];

                    $ge = $period['start'];
                    $le = $period['end'];

                    $collection->addFieldToFilter('date', array('ge' => Date::dateToJd($ge)));
                    $processedFilters['start_date'] = $ge;
                    $collection->addFieldToFilter('date', array('le' => Date::dateToJd($le)));
                    $processedFilters['end_date'] = $le;
                    break;

                case 'type':
                    switch ($value) {
                        case 'outcome':
                            $collection->addFieldToFilter('amount', array('lt' => 0));
                            $processedFilters['min_amount'] = 0;
                            break;

                        case 'income':
                            $collection->addFieldToFilter('amount', array('gt' => 0));
                            $processedFilters['max_amount'] = 0;
                            break;

                        case 'any_type':
                        default:
                            //no filter
                            break;
                    }
                    break;

                case 'status':
                    switch ($value) {
                        case 'uncategorized':
                            $collection->addFieldToFilter('category', array('null' => true))
                                ->addFieldToFilter('scat', array('null' => true));
                            $processedFilters['categories'] = null;
                            break;

                        case 'unreconciled':
                            $collection->addFieldToFilter('st', array('in' => Operation\Calculator::getUnreconciliedStatuses()));
                            $processedFilters['status'] = implode(',', Operation\Calculator::getUnreconciliedStatuses());
                            break;

                        case 'uncleared':
                            $collection->addFieldToFilter('st', array('in' => Operation\Calculator::getUnclearedStatuses()));
                            $processedFilters['status'] = implode(',', Operation\Calculator::getUnclearedStatuses());
                            break;

                        case 'reconciled':
                            $collection->addFieldToFilter('st', array('in' => Operation\Calculator::getReconciliedStatuses()));
                            $processedFilters['status'] = implode(',', Operation\Calculator::getReconciliedStatuses());
                            break;

                        case 'cleared':
                            $collection->addFieldToFilter('st', array('in' => Operation\Calculator::getClearedStatuses()));
                            $processedFilters['status'] = implode(',', Operation\Calculator::getClearedStatuses());
                            break;

                        case 'any_status':
                        default:
                            //no filter
                            break;
                    }
                    break;

                case 'search':
                    $value = trim($value);
                    if ($value) {
                        $collection->addFieldToFilter('aggregate_search', array('like' => "%$value%"));
                        $processedFilters['search'] = $value;
                    }
                    break;
            }
        }
        return $processedFilters;
    }

    public static function getPayeeLabelForDisplay(Operation $operation) {
        if ($operation->getPaymode() == Constants::PAYMODE_INTXFER) {
            return $operation->getXhb()->getAccount($operation->getDstAccount())->getName();
        }
        return $operation->getPayeeModel() ? $operation->getPayeeModel()->getName() : '';
    }
}