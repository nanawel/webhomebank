<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 11/11/15
 * Time: 14:37
 */

namespace app\models\core\Chart;


class Scatter extends AbstractChart
{
    protected $_defaultData = array(
        'type'     => 'scatter',
        'template' => 'common/chart/scatter.phtml',
        'width'    => 800,
        'height'   => 300
    );
}