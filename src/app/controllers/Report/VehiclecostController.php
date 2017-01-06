<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 30/06/15
 * Time: 18:37
 */

namespace app\controllers\Report;

use app\controllers\WhbController;
use app\helpers\whb\Chart;
use app\helpers\whb\VehicleCost;
use app\models\core\Chart\Bar;
use app\models\core\Main;
use app\models\whb\Chart\Scatter;
use app\models\core\Design;
use app\models\whb\Form\Element\CategoryFilter;
use app\models\whb\Form\Element\PeriodFilter;
use Xhb\Model\Category;
use Xhb\Model\Operation;
use Xhb\Model\Xhb\DateHelper;

class VehiclecostController extends WhbController
{
    protected function _beforeRoute($fw, $args = null) {
        parent::_beforeRoute($fw, $args);

        $this->_addCrumbsToTitle(array('Report', 'Vehicle Cost'));
        $this->_initAccount();
    }

    public function indexAction() {
        $xhb = $this->getXhbSession()->getModel();
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);

        $periodCode = $this->getRequestQuery('period') ? $this->getRequestQuery('period') : Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $periodObject = $xhb->getDateHelper()->getPeriodFromConstant($periodCode);
        $category = $this->getRequestQuery('category') ? $this->getRequestQuery('category') : $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge(array($category), $childrenIds);

        $consumptionSummaryData = $vehicleCostReport->getPeriodConsumptionSummaryData($periodObject, $categoryIds);
        $consumptionData = $vehicleCostReport->getPeriodConsumptionData($periodObject, $categoryIds);

        $filters = array();
        $categoryFilter = new CategoryFilter($xhb, array(
            'name'          => 'category',
            'id'            => 'filter-category',
            'value'         => $category,
            'class'         => 'filter-input'
        ));
        $filters['category'] = $categoryFilter;
        $periodFilter = new PeriodFilter($xhb, array(
            'name'          => 'period',
            'id'            => 'filter-period',
            'value'         => $periodCode,
            'class'         => 'filter-input'
        ));
        $filters['period'] = $periodFilter;

        Design::instance()->addJs('chartjs/Chart.js')
            ->addJs('chartjs/Chart.Scatter.js');    //FIXME Scale is buggy with minified JS
        $this->getView()
            ->setBlockTemplate('toolbar', 'common/toolbar.phtml')
            ->setBlockTemplate('summary', 'report/vehiclecost/index/summary.phtml')
            ->setBlockTemplate('charts', 'report/vehiclecost/index/charts.phtml')
            ->setData('FILTER_FORM_ACTION', $this->getUrl('*/*'))
            ->setData('FILTERS', $filters)
            ->setData('RESET_FILTERS_URL', $this->getUrl('*/*'))
            ->setData('CONSUMPTION_SUMMARY_DATA', $consumptionSummaryData)
            ->setData('CONSUMPTION_DATA', $consumptionData)
            ->setData('CONSUMPTION_CHART', new Scatter(array(
                'id'          => 'consumptionRatioChart',
                'title'       => 'Consumption',
                'data_url'    => $this->getUrl('*/consumptionChartData', array('_query' => '*')),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
                //'axis_type'   => Scatter::AXIS_TYPE_DATE_CURRENCY
            )))
            ->setData('FUEL_PRICE_EVOLUTION_CHART', new Scatter(array(
                'id'          => 'fuelPriceChart',
                'title'       => 'Fuel Price Evolution',
                'data_url'    => $this->getUrl('*/fuelPriceChartData', array('_query' => '*')),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
                'axis_type'   => Scatter::AXIS_TYPE_DATE_CURRENCY
            )))
            ->setData('TOTAL_DISTANCE_TRAVELED_CHART', new Scatter(array(
                'id'          => 'distanceTraveledChart',
                'title'       => 'Distance Traveled',
                'data_url'    => $this->getUrl('*/distanceTraveledChartData', array('_query' => '*')),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
            )))
            ->setData('DISTANCE_TRAVELED_BY_PERIOD_CHART', new Bar(array(
                'id'          => 'distanceTraveledByPeriodChart',
                'title'       => 'Distance Traveled by Period',
                'data_url'    => $this->getUrl('*/distanceTraveledByPeriodChartData', array('_query' => '*')),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
            )))
        ;
    }

    public function fuelPriceChartDataAction() {
        $xhb = $this->getXhbSession()->getModel();

        $periodCode = $this->getRequestQuery('period') ? $this->getRequestQuery('period') : Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $period = $xhb->getDateHelper()->getPeriodFromConstant($periodCode);
        $category = $this->getRequestQuery('category') ? $this->getRequestQuery('category') : $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge(array($category), $childrenIds);

        $fuelCostData = \app\helpers\whb\Chart\Vehiclecost::getFuelPriceData($xhb, $period, $categoryIds);

        $this->setPageConfig(array(
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ));
        $this->getView()->setData('DATA', $fuelCostData);
    }

    public function distanceTraveledChartDataAction() {
        $xhb = $this->getXhbSession()->getModel();

        $periodCode = $this->getRequestQuery('period') ? $this->getRequestQuery('period') : Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $period = $xhb->getDateHelper()->getPeriodFromConstant($periodCode);
        $category = $this->getRequestQuery('category') ? $this->getRequestQuery('category') : $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge(array($category), $childrenIds);

        $distanceTraveledData = \app\helpers\whb\Chart\Vehiclecost::getDistanceTraveledData($xhb, $period, $categoryIds);

        $this->setPageConfig(array(
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ));
        $this->getView()->setData('DATA', $distanceTraveledData);
    }

    public function consumptionChartDataAction() {
        $xhb = $this->getXhbSession()->getModel();

        $periodCode = $this->getRequestQuery('period') ? $this->getRequestQuery('period') : Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $period = $xhb->getDateHelper()->getPeriodFromConstant($periodCode);
        $category = $this->getRequestQuery('category') ? $this->getRequestQuery('category') : $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge(array($category), $childrenIds);

        $fuelCostData = \app\helpers\whb\Chart\Vehiclecost::getConsumptionData($xhb, $period, $categoryIds);

        $this->setPageConfig(array(
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ));
        $this->getView()->setData('DATA', $fuelCostData);
    }

    public function distanceTraveledByPeriodChartDataAction() {
        $xhb = $this->getXhbSession()->getModel();

        $periodCode = $this->getRequestQuery('period') ? $this->getRequestQuery('period') : Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $period = $xhb->getDateHelper()->getPeriodFromConstant($periodCode, 'P1W'); //TODO Handle dynamic interval
        $category = $this->getRequestQuery('category') ? $this->getRequestQuery('category') : $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge(array($category), $childrenIds);

        $distanceTraveledByPeriodData = \app\helpers\whb\Chart\Vehiclecost::getDistanceTraveledByPeriodData($xhb, $period, $categoryIds);

        $this->setPageConfig(array(
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ));
        $this->getView()->setData('DATA', $distanceTraveledByPeriodData);
    }
}