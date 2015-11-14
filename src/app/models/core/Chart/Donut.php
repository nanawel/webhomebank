<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 11/11/15
 * Time: 14:37
 */

namespace app\models\core\Chart;


class Donut extends AbstractChart
{
    protected $_defaultData = array(
        'type'     => 'donut',
        'template' => 'common/chart/donut.phtml',
        'width'    => 300,
        'height'   => 300
    );
}