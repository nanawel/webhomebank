<?php

namespace app\models\core\Chart;


class Doughnut extends AbstractChart
{
    protected $_defaultData = [
        'type'     => 'doughnut',
        'template' => 'common/chart/doughnut.phtml',
        'width'    => 'auto',
        'height'   => 'auto'
    ];
}
