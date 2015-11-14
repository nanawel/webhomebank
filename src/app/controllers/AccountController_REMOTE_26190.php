<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 30/06/15
 * Time: 18:37
 */

namespace app\controllers;

use app\helpers\core\Output;
use app\helpers\whb\AccountOperation;
use app\helpers\whb\Chart;
use app\models\core\Design;
use app\models\core\I18n;
use app\models\core\Log;
use xhb\models\Constants;
use xhb\models\Operation\Collection;

class AccountController extends WhbController
{
    protected function _beforeRoute($fw, $args = null) {
        parent::_beforeRoute($fw, $args);

        $this->_addCrumbsToTitle(array('Accounts'));
    }

    /**
     * @param \Base $fw
     */
    public function indexAction() {
        $this->_reroute('/account/grid', true);
    }

    protected function _gridActionBefore() {
        /*
         * Nothing, just here as an example.
         * Called before _renderFromCache() from beforeRoute(), may return FALSE to stop request processing.
         */
    }

    public function gridAction() {
        $xhbModel = $this->getXhbSession()->getModel();
        $gridDataCacheKey = 'gridData_'. $xhbModel->getUniqueKey();
        $totalDataCacheKey = 'totalData_'. $xhbModel->getUniqueKey();

        if (!($gridData = $this->_loadCache($gridDataCacheKey)) || !($totalData = $this->_loadCache($totalDataCacheKey))) {
            $gridData = array();
            $totalData = array();
            /* @var $account \xhb\models\Account */
            foreach($xhbModel->getAccountCollection() as $account) {
                $type = $account->getType(true);
                $accountData = array(
                    'account' => $account,
                    'balances' => array(
                        Constants::BALANCE_TYPE_BANK => $account->getBankBalance(),
                        Constants::BALANCE_TYPE_TODAY => $account->getTodayBalance(),
                        Constants::BALANCE_TYPE_FUTURE => $account->getFutureBalance()
                    )
                );
                $gridData[$type][] = $accountData;

                if (!isset($totalData[$type])) {
                    $totalData[$type] = array(
                        Constants::BALANCE_TYPE_BANK => 0,
                        Constants::BALANCE_TYPE_TODAY => 0,
                        Constants::BALANCE_TYPE_FUTURE => 0
                    );
                }
                if (!isset($totalData['grand_total'])) {
                    $totalData['grand_total'] = array(
                        Constants::BALANCE_TYPE_BANK => 0,
                        Constants::BALANCE_TYPE_TODAY => 0,
                        Constants::BALANCE_TYPE_FUTURE => 0
                    );
                }

                $totalData[$type][Constants::BALANCE_TYPE_BANK] += $accountData['balances'][Constants::BALANCE_TYPE_BANK];
                $totalData[$type][Constants::BALANCE_TYPE_TODAY] += $accountData['balances'][Constants::BALANCE_TYPE_TODAY];
                $totalData[$type][Constants::BALANCE_TYPE_FUTURE] += $accountData['balances'][Constants::BALANCE_TYPE_FUTURE];
                $totalData['grand_total'][Constants::BALANCE_TYPE_BANK] += $accountData['balances'][Constants::BALANCE_TYPE_BANK];
                $totalData['grand_total'][Constants::BALANCE_TYPE_TODAY] += $accountData['balances'][Constants::BALANCE_TYPE_TODAY];
                $totalData['grand_total'][Constants::BALANCE_TYPE_FUTURE] += $accountData['balances'][Constants::BALANCE_TYPE_FUTURE];
            }

            $this->_saveCache($gridDataCacheKey, $gridData)
                ->_saveCache($totalDataCacheKey, $totalData);
        }


        $topSpendingChartFilters = $balanceReportChartFilters = array(AccountOperation::getPredefinedCollectionFilters()['period']);

        Design::instance()->addJs('chartjs/Chart.min.js');
        $this->getView()
            ->setBlockTemplate('charts', 'account/grid/charts.phtml')
            ->setData('GRID_DATA', $gridData)
            ->setData('TOTAL_DATA', $totalData)
            ->setData('TOP_SPENDING_CHART_DATA_URL', $this->getUrl('*/gridChartTopSpending'))
            ->setData('TOP_SPENDING_CHART_FILTERS', $topSpendingChartFilters)
            ->setData('BALANCE_REPORT_CHART_DATA_URL', $this->getUrl('*/gridChartBalanceReport'))
            ->setData('BALANCE_REPORT_CHART_FILTERS', $balanceReportChartFilters);
    }

    public function gridChartTopSpendingAction($fw, $args) {
        $xhbModel = $this->getXhbSession()->getModel();

        $collFilters = array(
            'period' => $this->getRequestQuery('period') ? $this->getRequestQuery('period') : 'this_month',
            'type'   => 'outcome'
        );

        $opColl = $xhbModel->getOperationCollection();
        AccountOperation::applyFiltersOnCollection($opColl, $collFilters);
        $opColl->addFieldToFilter('paymode', array('neq' => Constants::PAYMODE_INTXFER));

        // FIXME does not handle split amounts yet
        $maxResults = 6;
        $sumByCategory = Chart::sumBy($opColl, 'category', 'amount', 0, $maxResults);

        $sumsData = array();
        $n = 0;
        foreach($sumByCategory as $catId => $sum) {
            $cat = $xhbModel->getCategory($catId);
            if (!$cat) {
                $catName = $n == ($maxResults - 1) ? $this->__('Other') : $this->__('(Unknown)');
            }
            else {
                $catName = $cat->getFullname();
            }
            $sumsData[] = array(
                'value'          => abs(round($sum)),
                'label'          => $catName . ' (' . I18n::instance()->currency(abs(round($sum))) . ')',
                'color'          => Output::rgbToCss(Chart::getColor($n++))
            );
        }

        $this->setPageConfig(array(
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ));
        $this->getView()->setData('DATA', $sumsData);
    }

    public function gridChartBalanceReportAction($fw, $args) {
        $collFilters = array(
            'period' => $this->getRequestQuery('period') ? $this->getRequestQuery('period') : 'this_month'
        );

        $xhb = $this->getXhbSession()->getModel();
        $accountCollection = $xhb->getAccountCollection()
            ->addFieldToFilter('type', Constants::ACC_TYPE_BANK);
        $accountIds = $accountCollection->getAllIds();

        $chartData = Chart::getBalanceReportData($xhb, $collFilters, $accountIds, $this->__('Balance Report'));

        $this->setPageConfig(array(
                'template' => 'data/json.phtml',
                'mime'     => 'application/json'
            ));
        $this->getView()->setData('DATA', $chartData);
    }
} 