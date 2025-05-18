<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 11/11/15
 * Time: 14:37
 */

namespace app\models\core\Chart;


class Line extends AbstractChart
{
    public const SCALE_Y_UNIT_CURRENCY = '__currency__';
    public const SCALE_Y_UNIT_NUMBER = '__number__';
    public const SCALE_Y_UNIT_CUSTOM = '__custom__';

    protected $_defaultData = array(
        'type'     => 'line',
        'template' => 'common/chart/line.phtml',
        'width'    => 800,
        'height'   => 300,
        'class'    => 'toolbar-top-right',
        'scale_y_unit' => self::SCALE_Y_UNIT_CURRENCY
    );

    public function getTooltipJsCallback($jsValueVar) {
        switch ($this->getData('scale_y_unit')) {
            case self::SCALE_Y_UNIT_CURRENCY:
                return "i18n.formatCurrency($jsValueVar)";
            case self::SCALE_Y_UNIT_CUSTOM:
                return $this->getData('scale_y_unit_custom');
            case self::SCALE_Y_UNIT_NUMBER:
            default:
                return "i18n.formatNumber($jsValueVar)";
        }
    }
}
