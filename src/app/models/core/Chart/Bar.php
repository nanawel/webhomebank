<?php

namespace app\models\core\Chart;


class Bar extends AbstractChart
{
    protected $_defaultData = [
        'type'     => 'bar',
        'template' => 'common/chart/bar.phtml',
        'width'    => 'auto',
        'height'   => 'auto'
    ];
}
