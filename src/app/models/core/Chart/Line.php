<?php

namespace app\models\core\Chart;


class Line extends AbstractChart
{
    protected $_defaultData = [
        'type'         => 'line',
        'template'     => 'common/chart/line.phtml',
        'width'        => 'auto',
        'height'       => 'auto',
        'class'        => 'toolbar-top-right',
        'scale_y_unit' => self::SCALE_Y_UNIT_CURRENCY
    ];
}
