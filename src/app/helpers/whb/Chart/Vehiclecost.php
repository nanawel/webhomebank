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

    public static function getFuelPriceData(Xhb $xhb, \DatePeriod $period, $categoryIds) {
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);
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
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);
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

    public static function getDistanceTraveledByPeriodData(Xhb $xhb, \DatePeriod $period, $categoryIds) {
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);
        $distanceTraveledByPeriod = $vehicleCostReport->getDistanceTraveledByPeriod($period, $categoryIds);

        $return = array(
            'labels'   => array(),
            'datasets' => array()
        );

        $return['datasets'][0] = array(
            'label'                => I18n::instance()->tr('Distance Traveled by Period'),
            'strokeColor'          => Output::rgbToCss(Chart::getColor(self::METER_CHART_COLOR_IDX)),
            'pointColor'           => Output::rgbToCss(Chart::getColor(self::METER_CHART_COLOR_IDX)),
            'pointHighlightFill'   => '#fff',
            'pointHighlightStroke' => '#bbb',
            'data'                 => array()
        );

        foreach($distanceTraveledByPeriod as $dtbp) {
            $return['datasets'][0]['data'][] = array(
                'x' => $dtbp['date']->getTimestamp(),
                'y' => $dtbp['distance']
            );
        }
        return $return;
    }

    public static function getConsumptionData(Xhb $xhb, \DatePeriod $period, $categoryIds) {
        $vehicleCostReport = new \Xhb\Model\Report\VehicleCost($xhb);
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