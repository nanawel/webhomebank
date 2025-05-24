<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 14/07/15
 * Time: 18:34
 */

namespace app\helpers\whb\Chart;

use app\helpers\core\Output;
use app\helpers\whb\Chart;
use app\models\core\I18n;
use Xhb\Model\Constants;
use Xhb\Model\Operation\Calculator;
use Xhb\Model\Operation\Collection;
use Xhb\Model\Xhb;
use Xhb\Model\Resource\AbstractCollection;

class Vehiclecost
{
    const FUEL_PRICE_CHART_COLOR_IDX = 10;

    const METER_CHART_COLOR_IDX = 8;

    const CONSUMPTION_CHART_COLOR_IDX = 6;

    public static function getFuelPriceData(Xhb $xhb, \DatePeriod $period, $categoryIds): array {
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);
        $consumptionData = $vehicleCostReport->getPeriodConsumptionData($period, $categoryIds);

        $return = [
            'labels'   => [],
            'datasets' => []
        ];

        $return['datasets'][0] = [
            'label'                => I18n::instance()->tr('Price per vol.'),
            'strokeColor'          => Output::rgbToCss(Chart::getColor(self::FUEL_PRICE_CHART_COLOR_IDX)),
            'pointColor'           => Output::rgbToCss(Chart::getColor(self::FUEL_PRICE_CHART_COLOR_IDX)),
            'pointHighlightFill'   => '#fff',
            'pointHighlightStroke' => '#bbb',
            'data'                 => []
        ];

        foreach($consumptionData as $cd) {
            $return['datasets'][0]['data'][] = [
                'x' => $cd['date']->getTimestamp(),
                'y' => $cd['price']
            ];
        }

        return $return;
    }

    public static function getDistanceTraveledData(Xhb $xhb, \DatePeriod $period, $categoryIds): array {
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);
        $consumptionData = $vehicleCostReport->getPeriodConsumptionData($period, $categoryIds);

        $return = [
            'labels'   => [],
            'datasets' => []
        ];

        $return['datasets'][0] = [
            'label'                => I18n::instance()->tr('Distance Traveled'),
            'strokeColor'          => Output::rgbToCss(Chart::getColor(self::METER_CHART_COLOR_IDX)),
            'pointColor'           => Output::rgbToCss(Chart::getColor(self::METER_CHART_COLOR_IDX)),
            'pointHighlightFill'   => '#fff',
            'pointHighlightStroke' => '#bbb',
            'data'                 => []
        ];

        foreach($consumptionData as $cd) {
            $return['datasets'][0]['data'][] = [
                'x' => $cd['date']->getTimestamp(),
                'y' => $cd['meter']
            ];
        }

        return $return;
    }

    /**
     * @return array{labels: non-empty-list, datasets: array{array{label: mixed, strokeColor: mixed, fillColor: mixed, highlightFill: '#fff', highlightStroke: '#bbb', data: non-empty-list}}}|array{labels: array{}, datasets: array{array{label: mixed, strokeColor: mixed, fillColor: mixed, highlightFill: '#fff', highlightStroke: '#bbb', data: array{}}}}
     */
    public static function getDistanceTraveledByPeriodData(Xhb $xhb, \DatePeriod $period, $categoryIds): array {
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);
        $distanceTraveledByPeriod = $vehicleCostReport->getDistanceTraveledByPeriod($period, $categoryIds);

        $return = [
            'labels'   => [],
            'datasets' => []
        ];

        $return['datasets'][0] = [
            'label'                => I18n::instance()->tr('Distance Traveled by Period'),
            'strokeColor'          => Output::rgbToCss(Chart::getColor(self::METER_CHART_COLOR_IDX)),
            'fillColor'            => Output::rgbToCss(Chart::getColor(self::METER_CHART_COLOR_IDX)),
            'highlightFill'        => '#fff',
            'highlightStroke'      => '#bbb',
            'data'                 => []
        ];

        foreach($distanceTraveledByPeriod as $dtbp) {
            $return['labels'][] = $dtbp['date']->getTimestamp();
            $return['datasets'][0]['data'][] = $dtbp['distance'];
        }

        return $return;
    }

    public static function getConsumptionData(Xhb $xhb, \DatePeriod $period, $categoryIds): array {
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);
        $consumptionData = $vehicleCostReport->getPeriodConsumptionData($period, $categoryIds);

        $return = [
            'labels'   => [],
            'datasets' => []
        ];

        $return['datasets'][0] = [
            'label'                => I18n::instance()->tr('Consumption'),
            'strokeColor'          => Output::rgbToCss(Chart::getColor(self::CONSUMPTION_CHART_COLOR_IDX)),
            'pointColor'           => Output::rgbToCss(Chart::getColor(self::CONSUMPTION_CHART_COLOR_IDX)),
            'pointHighlightFill'   => '#fff',
            'pointHighlightStroke' => '#bbb',
            'data'                 => []
        ];

        foreach($consumptionData as $cd) {
            $return['datasets'][0]['data'][] = [
                'x' => $cd['date']->getTimestamp(),
                'y' => $cd['per-100']
            ];
        }

        return $return;
    }
}