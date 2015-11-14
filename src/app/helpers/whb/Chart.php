<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 14/07/15
 * Time: 18:34
 */

namespace app\helpers\whb;

use xhb\models\Constants;
use xhb\models\Operation\Collection;
use xhb\models\Xhb;
use xhb\models\Resource\AbstractCollection;

class Chart
{
    /**
     * TODO Should be moved to Calculator
     *
     * @param AbstractCollection $collection
     * @param string $labelField
     * @param string $valueField
     * @param float $initial
     * @param int $maxResults
     * @return array
     */
    public static function sumBy(AbstractCollection $collection, $labelField, $valueField, $initial = 0.0, $maxResults = 0) {
        $sums = array();
        /* @var $item \xhb\util\MagicObject */
        foreach($collection as $item) {
            $label = $item->getDataUsingMethod($labelField);
            if (!isset($sums[$label])) {
                $sums[$label] = $initial;
            }
            $sums[$label] += $item->getDataUsingMethod($valueField);
        }
        asort($sums, SORT_DESC);

        if ($maxResults > 0 && count($sums) > $maxResults) {
            $i = 0;
            $remainder = 0;
            $newSums = array();
            foreach($sums as $key => $value) {
                if (++$i < $maxResults) {
                    $newSums[$key] = $value;
                    continue;
                }
                if ($value < 0) {
                    $remainder += $value;
                }
            }
            $newSums['__REMAINDER__'] = $remainder;
            $sums = $newSums;
        }

        return $sums;
    }

    public static function getColor($n) {
        return Constants::$CHARTS_DEFAULT_COLORS[$n % count(Constants::$CHARTS_DEFAULT_COLORS)];
    }
} 