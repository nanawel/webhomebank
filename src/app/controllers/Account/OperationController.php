<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 30/06/15
 * Time: 18:37
 */

namespace app\controllers\Account;

use app\controllers\WhbController;
use app\helpers\core\Output;
use app\helpers\whb\AccountOperation;
use app\helpers\whb\Chart;
use app\models\core\Chart\Line;
use app\models\core\Design;
use app\models\core\I18n;
use app\models\core\Log;
use app\models\core\Main;
use app\models\whb\App;
use app\models\whb\Chart\Scatter;
use app\models\whb\Form\Element\PeriodFilter;
use app\models\whb\Form\Element\SearchFilter;
use app\models\whb\Form\Element\StatusFilter;
use app\models\whb\Form\Element\TypeFilter;
use Xhb\Model\Account;
use Xhb\Model\Category;
use Xhb\Model\Constants;
use Xhb\Model\Operation;
use Xhb\Model\Xhb\DateHelper;

class OperationController extends WhbController
{
    protected function _beforeRoute($fw, $args = null) {
        parent::_beforeRoute($fw, $args);

        $this->_addCrumbsToTitle(array('Account', 'Operations'));
        $this->_initAccount();
    }

    public function _initAccount() {
        $accountId = $this->_getRequestParam('account_id');
        $account = $this->getXhbSession()->getModel()->getAccount($accountId);
        if (empty($account)) {
            $message = 'Missing or invalid account ID.';
            Log::instance()->log($message . ' (' . $this->getFullActionName() . ')');
            $this->_error($message);
            $this->_redirectReferer();
        }
        $this->_fw->set('current_account', $account);
    }

    /**
     * @return Account
     */
    public function getAccount() {
        return $this->_fw->get('current_account');
    }

    public function indexAction() {
        $xhb = $this->getXhbSession()->getModel();
        $order = $this->getRequestQuery('order');
        $dir = $this->getRequestQuery('dir');
        if (!$order) {
            $order = 'date';
            $dir = SORT_ASC;
        }
        if (!$dir) {
            $dir = SORT_ASC;
        }
        $currentOrder = array($order => $dir);

        $coll = $this->getAccount()->getOperationCollection()
            ->orderBy($order, $dir);

        $query = $this->getRequestQuery();
        if (!isset($query['period'])) {
            $query['period'] = Main::app()->getConfig('DEFAULT_OPERATIONS_PERIOD');
        }

        $filters = AccountOperation::applyFiltersOnCollection($coll, $query);

        // Speed up collection loading by not setting aggregated fields if not needed
        if (empty($filters['search'])) {
            $coll->setFlag('skip_aggregated_fields');
        }

        $filters = array();
        $periodFilter = new PeriodFilter($xhb, array(
            'name'          => 'period',
            'id'            => 'filter-period',
            'value'         => $query['period'],
            'class'         => 'filter-input'
        ));
        $filters['period'] = $periodFilter;
        $typeFilter = new TypeFilter($xhb, array(
            'name'          => 'type',
            'id'            => 'filter-type',
            'value'         => isset($query['type']) ? $query['type'] : null,
            'class'         => 'filter-input'
        ));
        $filters['type'] = $typeFilter;
        $statusFilter = new StatusFilter($xhb, array(
            'name'          => 'status',
            'id'            => 'filter-status',
            'value'         => isset($query['status']) ? $query['status'] : null,
            'class'         => 'filter-input'
        ));
        $filters['status'] = $statusFilter;
        $searchFilter = new SearchFilter($xhb, array(
            'name'          => 'search',
            'id'            => 'filter-search',
            'value'         => isset($query['search']) ? $query['search'] : null,
            'class'         => 'filter-input'
        ));
        $filters['search'] = $searchFilter;


        Design::instance()->addJs('chartjs/Chart.min.js')
            ->addJs('chartjs/Chart.Scatter.js');    //FIXME Scale is buggy with minified JS
        $this->getView()
            ->setBlockTemplate('operation_toolbar', 'common/toolbar.phtml')
            ->setBlockTemplate('account_summary', 'account/operation/index/summary.phtml')
            ->setBlockTemplate('charts', 'account/operation/index/charts.phtml')
            ->setData('OPERATION_COLLECTION', $coll)
            ->setData('FILTER_FORM_ACTION', $this->getUrl('*/*/*'))
            ->setData('RESET_FILTERS_URL', $this->getUrl('*/*/*'))
            ->setData('FILTERS', $filters)
            ->setData('CURRENT_ORDER', $currentOrder)
            ->setData('BALANCE_REPORT_CHART', new Scatter(array(
                'id'       => 'balanceReportChart',
                'title'    => 'Balance Report',
                'data_url' => $this->getUrl('*/balanceReportChartData/*', array('_query' => '*')),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
                'axis_type'   => Scatter::AXIS_TYPE_DATE_CURRENCY
            )))
        ;
    }

    public function balanceReportChartDataAction() {
        $collFilters = array(
            'period' => $this->getRequestQuery('period') ? $this->getRequestQuery('period') : Main::app()->getConfig('DEFAULT_OPERATIONS_PERIOD'),
        );

        $chartData = Chart\Operation::getBalanceReportData(
            $this->getXhbSession()->getModel(),
            $collFilters,
            array($this->getAccount()->getId())
        );

        $this->setPageConfig(array(
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ));
        $this->getView()->setData('DATA', $chartData);
    }
} 