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
use xhb\models\Constants;
use xhb\models\Operation\Calculator;
use xhb\models\Operation\Collection;
use xhb\models\Xhb;
use xhb\models\Resource\AbstractCollection;

class Vehiclecost
{
    const FUEL_PRICE_CHART_COLOR_IDX = 10;
    const METER_CHART_COLOR_IDX = 8;
    const CONSUMPTION_CHART_COLOR_IDX = 6;

    public static function getFuelPriceData(Xhb $xhb, \DatePeriod $period, $categoryIds) {
        $vehicleCostReport = new \xhb\models\Report\VehicleCost($xhb);
        $consumptionData = $vehicleCostReport->getPeriodConsumptionData($period, $categoryIds);

        $return = array(
            'labels'   => array(),
            'datasets' => array()
        );

        $return['datasets'][0] = array(
            'label'                => I18n::instance()->tr('Price per vol.'),
            'strokeColor'          => Output::rgbToCss(Chart::getColor(self::FUEL_PRICE_CHART_COLOR_IDX)),
            'pointColor'           => Output::rgbToCss(Chart::getColor(self::FUEL_PRICE_CHART_COLOR_IDX)),
            'pointHighlightFill'   => '#fff',
            'pointHighlightStroke' => '#bbb',
            'data'                 => array()
        );

        foreach($consumptionData as $cd) {
            $return['datasets'][0]['data'][] = array(
                'x' => $cd['date']->getTimestamp(),
                'y' => $cd['price']
            );
        }
        return $return;
    }

    public static function getDistanceTraveledData(Xhb $xhb, \DatePeriod $period, $categoryIds) {
        $vehicleCostReport = new \xhb\models\Report\VehicleCost($xhb);
        $consumptionData = $vehicleCostReport->getPeriodConsumptionData($period, $categoryIds);

        $return = array(
            'labels'   => array(),
            'datasets' => array()
        );

        $return['datasets'][0] = array(
            'label'                => I18n::instance()->tr('Distance Traveled'),
            'strokeColor'          => Output::rgbToCss(Chart::getColor(self::METER_CHART_COLOR_IDX)),
            'pointColor'           => Output::rgbToCss(Chart::getColor(self::METER_CHART_COLOR_IDX)),
            'pointHighlightFill'   => '#fff',
            'pointHighlightStroke' => '#bbb',
            'data'                 => array()
        );

        foreach($consumptionData as $cd) {
            $return['datasets'][0]['data'][] = array(
                'x' => $cd['date']->getTimestamp(),
                'y' => $cd['meter']
            );
        }
        return $return;
    }

    public static function getConsumptionData(Xhb $xhb, \DatePeriod $period, $categoryIds) {
        $vehicleCostReport = new \xhb\models\Report\VehicleCost($xhb);
        $consumptionData = $vehicleCostReport->getPeriodConsumptionData($period, $categoryIds);

        $return = array(
            'labels'   => array(),
            'datasets' => array()
        );

        $return['datasets'][0] = array(
            'label'                => I18n::instance()->tr('Consumption'),
            'strokeColor'          => Output::rgbToCss(Chart::getColor(self::CONSUMPTION_CHART_COLOR_IDX)),
            'pointColor'           => Output::rgbToCss(Chart::getColor(self::CONSUMPTION_CHART_COLOR_IDX)),
            'pointHighlightFill'   => '#fff',
            'pointHighlightStroke' => '#bbb',
            'data'                 => array()
        );

        foreach($consumptionData as $cd) {
            $return['datasets'][0]['data'][] = array(
                'x' => $cd['date']->getTimestamp(),
                'y' => $cd['per-100']
            );
        }
        return $return;
    }
}