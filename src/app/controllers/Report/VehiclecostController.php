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
use app\models\core\Chart\Line;
use app\models\core\Main;
use app\models\core\Design;
use app\models\whb\Form\Element\CategoryFilter;
use app\models\whb\Form\Element\PeriodFilter;
use Xhb\Model\Category;
use Xhb\Model\Operation;
use Xhb\Model\Xhb\DateHelper;

class VehiclecostController extends WhbController
{
    #[\ReturnTypeWillChange]
    protected function _beforeRoute($fw, $args = null) {
        parent::_beforeRoute($fw, $args);

        $this->_addCrumbsToTitle(['Report', 'Vehicle Cost']);
        $this->_initAccount();
    }

    public function indexAction(): void {
        $xhb = $this->getXhbSession()->getModel();
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);

        $periodCode = $this->getRequestQuery('period') ?: Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $periodObject = $xhb->getDateHelper()->getPeriodFromConstant($periodCode);
        $category = $this->getRequestQuery('category') ?: $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge([$category], $childrenIds);

        $consumptionSummaryData = $vehicleCostReport->getPeriodConsumptionSummaryData($periodObject, $categoryIds);
        $consumptionData = $vehicleCostReport->getPeriodConsumptionData($periodObject, $categoryIds);

        $filters = [];
        $categoryFilter = new CategoryFilter($xhb, [
            'name'          => 'category',
            'id'            => 'filter-category',
            'value'         => $category,
            'class'         => 'filter-input'
        ]);
        $filters['category'] = $categoryFilter;
        $periodFilter = new PeriodFilter($xhb, [
            'name'          => 'period',
            'id'            => 'filter-period',
            'value'         => $periodCode,
            'class'         => 'filter-input'
        ]);
        $filters['period'] = $periodFilter;

        $this->getView()
            ->setBlockTemplate('toolbar', 'common/toolbar.phtml')
            ->setBlockTemplate('summary', 'report/vehiclecost/index/summary.phtml')
            ->setBlockTemplate('charts', 'report/vehiclecost/index/charts.phtml')
            ->setData('FILTER_FORM_ACTION', $this->getUrl('*/*'))
            ->setData('FILTERS', $filters)
            ->setData('RESET_FILTERS_URL', $this->getUrl('*/*'))
            ->setData('CONSUMPTION_SUMMARY_DATA', $consumptionSummaryData)
            ->setData('CONSUMPTION_DATA', $consumptionData)
            ->setData('CONSUMPTION_CHART', new Line([
                'id'          => 'consumptionRatioChart',
                'title'       => 'Consumption',
                'data_url'    => $this->getUrl('*/consumptionChartData', ['_query' => '*']),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
                'scale_y_unit' => Line::SCALE_Y_UNIT_CUSTOM,
                'scale_y_unit_custom' => '`${i18n.formatNumber(data.parsed.y)} L/100km`'
            ]))
            ->setData('TOTAL_DISTANCE_TRAVELED_CHART', new Line([
                'id'          => 'distanceTraveledChart',
                'title'       => 'Distance Traveled',
                'data_url'    => $this->getUrl('*/distanceTraveledChartData', ['_query' => '*']),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
            ]))
            ->setData('DISTANCE_TRAVELED_BY_PERIOD_CHART', new Bar([
                'id'          => 'distanceTraveledByPeriodChart',
                'title'       => 'Distance Traveled by Period',
                'data_url'    => $this->getUrl('*/distanceTraveledByPeriodChartData', ['_query' => '*']),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
                'scale_y_unit' => Line::SCALE_Y_UNIT_CUSTOM,
                'scale_y_unit_custom' => '`${data.parsed.y}km`'
            ]))
            ->setData('FUEL_PRICE_EVOLUTION_CHART', new Line([
                'id'          => 'fuelPriceChart',
                'title'       => 'Fuel Price Evolution',
                'data_url'    => $this->getUrl('*/fuelPriceChartData', ['_query' => '*']),
                'class'       => 'toolbar-top-right',
                'show_legend' => false,
                'scale_y_unit' => Line::SCALE_Y_UNIT_CUSTOM,
                'scale_y_unit_custom' => '`${i18n.formatCurrency(data.parsed.y)}/L`'
            ]))
        ;
    }

    public function fuelPriceChartDataAction(): void {
        $xhb = $this->getXhbSession()->getModel();

        $periodCode = $this->getRequestQuery('period') ?: Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $period = $xhb->getDateHelper()->getPeriodFromConstant($periodCode);
        $category = $this->getRequestQuery('category') ?: $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge([$category], $childrenIds);

        $fuelCostData = \app\helpers\whb\Chart\Vehiclecost::getFuelPriceData($xhb, $period, $categoryIds);

        $this->setPageConfig([
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ]);
        $this->getView()->setData('DATA', $fuelCostData);
    }

    public function distanceTraveledChartDataAction(): void {
        $xhb = $this->getXhbSession()->getModel();

        $periodCode = $this->getRequestQuery('period') ?: Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $period = $xhb->getDateHelper()->getPeriodFromConstant($periodCode);
        $category = $this->getRequestQuery('category') ?: $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge([$category], $childrenIds);

        $distanceTraveledData = \app\helpers\whb\Chart\Vehiclecost::getDistanceTraveledData($xhb, $period, $categoryIds);

        $this->setPageConfig([
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ]);
        $this->getView()->setData('DATA', $distanceTraveledData);
    }

    public function consumptionChartDataAction(): void {
        $xhb = $this->getXhbSession()->getModel();

        $periodCode = $this->getRequestQuery('period') ?: Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $period = $xhb->getDateHelper()->getPeriodFromConstant($periodCode);
        $category = $this->getRequestQuery('category') ?: $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge([$category], $childrenIds);

        $fuelCostData = \app\helpers\whb\Chart\Vehiclecost::getConsumptionData($xhb, $period, $categoryIds);

        $this->setPageConfig([
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ]);
        $this->getView()->setData('DATA', $fuelCostData);
    }

    public function distanceTraveledByPeriodChartDataAction(): void {
        $xhb = $this->getXhbSession()->getModel();

        $periodCode = $this->getRequestQuery('period') ?: Main::app()->getConfig('DEFAULT_VEHICLES_PERIOD');
        $period = $xhb->getDateHelper()->getPeriodFromConstant($periodCode, 'P1W'); //TODO Handle dynamic interval
        $category = $this->getRequestQuery('category') ?: $xhb->getCarCategory();

        /* @var $category Category */
        $childrenIds = $xhb->getCategory($category)->getChildrenCategories()
            ->getAllIds();
        $categoryIds = array_merge([$category], $childrenIds);

        $distanceTraveledByPeriodData = \app\helpers\whb\Chart\Vehiclecost::getDistanceTraveledByPeriodData($xhb, $period, $categoryIds);

        $this->setPageConfig([
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ]);
        $this->getView()->setData('DATA', $distanceTraveledByPeriodData);
    }
}
