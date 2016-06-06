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
use app\models\core\Chart\Donut;
use app\models\core\Main;
use app\models\whb\Chart\Scatter;
use app\models\core\Design;
use app\models\core\I18n;
use app\models\whb\Form\Element\PeriodFilter;
use xhb\models\Constants;
use xhb\models\Operation\Collection;
use xhb\models\Xhb\DateHelper;

class AccountController extends WhbController
{
    protected function _beforeRoute($fw, $args = null) {
        parent::_beforeRoute($fw, $args);

        $this->_addCrumbsToTitle(array('Accounts'));
    }

    protected function _indexActionBefore() {
        /*
         * Nothing, just here as an example.
         * Called before _renderFromCache() from beforeRoute(), may return FALSE to stop request processing.
         */
    }

    /**
     * @param \Base $fw
     */
    public function indexAction() {
        $xhb = $this->getXhbSession()->getModel();
        $gridDataCacheKey = 'gridData_'. $xhb->getUniqueKey();
        $totalDataCacheKey = 'totalData_'. $xhb->getUniqueKey();

        if (!($gridData = $this->_loadCache($gridDataCacheKey)) || !($totalData = $this->_loadCache($totalDataCacheKey))) {
            $gridData = array();
            $totalData = array();
            /* @var $account \xhb\models\Account */
            foreach($xhb->getAccountCollection() as $account) {
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


        Design::instance()->addJs('chartjs/Chart.min.js')
            ->addJs('chartjs/Chart.Scatter.js');    //FIXME Scale is buggy with minified JS
        $this->getView()
            ->setBlockTemplate('charts', 'account/index/charts.phtml')
            ->setData('GRID_DATA', $gridData)
            ->setData('TOTAL_DATA', $totalData)
            ->setData('TOP_SPENDING_CHART', new Donut(array(
                'id'       => 'topSpendingChart',
                'title'    => 'Where your money goes',
                'data_url' => $this->getUrl('*/topSpendingChartData'),
                'filters'  => array(
                    new PeriodFilter($xhb, array(
                        'name'  => 'period',
                        'value' => Main::app()->getConfig('DEFAULT_OPERATIONS_PERIOD')
                    ))
                )
            )))
            ->setData('BALANCE_REPORT_CHART', new Scatter(array(
                'id'       => 'balanceReportChart',
                'title'    => 'General Balance Report',
                'data_url' => $this->getUrl('*/balanceReportChartData'),
                'filters'  => array(
                    new PeriodFilter($xhb, array(
                        'name' => 'period',
                        'value' => Main::app()->getConfig('DEFAULT_OPERATIONS_PERIOD')
                    ))
                ),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
                'footer_note' => $this->__('Only bank accounts are shown here.'),
                'axis_type'   => Scatter::AXIS_TYPE_DATE_CURRENCY
            )))
        ;
    }

    public function topSpendingChartDataAction() {
        $xhb = $this->getXhbSession()->getModel();
        $collFilters = array(
            'period' => $this->getRequestQuery('period') ? $this->getRequestQuery('period') : Main::app()->getConfig('DEFAULT_OPERATIONS_PERIOD')
        );

        $sumsData = Chart\Operation::getTopSpendingReportData($xhb, $collFilters);

        $this->setPageConfig(array(
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ));
        $this->getView()->setData('DATA', $sumsData);
    }

    public function balanceReportChartDataAction() {
        $xhb = $this->getXhbSession()->getModel();
        $collFilters = array(
            'period' => $this->getRequestQuery('period') ? $this->getRequestQuery('period') : Main::app()->getConfig('DEFAULT_OPERATIONS_PERIOD')
        );

        $accountCollection = $xhb->getAccountCollection()
            ->addFieldToFilter('type', Constants::ACC_TYPE_BANK);
        $accountIds = $accountCollection->getAllIds();

        $chartData = Chart\Operation::getBalanceReportData($xhb, $collFilters, $accountIds, $this->__('Balance Report'));

        $this->setPageConfig(array(
                'template' => 'data/json.phtml',
                'mime'     => 'application/json'
            ));
        $this->getView()->setData('DATA', $chartData);
    }
} 