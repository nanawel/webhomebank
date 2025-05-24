<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 11/07/15
 * Time: 10:48
 */

namespace Xhb\Model\Report;

use Xhb\Model\Operation;
use Xhb\Model\Xhb;
use Xhb\Model\XhbModel;

/**
 * Class VehicleCost
 *
 * @method Xhb getXhb()
 *
 * @package Xhb\Model\Report
 */
class VehicleCost extends XhbModel
{
    const CONSUMPTION_WORDING_PATTERN = '/(?:(?:d=(?P<dist>\d+))|(?:v(?P<op>[~=])(?P<volume>[\d\.]+)))/';

    /**
     * @var Category[]
     */
    protected $_carCategories = [];

    /**
     * @var array
     */
    protected $_consumptionData = [];

    public function __construct(Xhb $xhb, array $data = []) {
        parent::__construct($data);
        $this->setXhb($xhb);
    }

    /**
     * @return Category[]
     */
    public function getCarCategories() {
        if (!$this->_carCategories) {
            $xhb = $this->getXhb();
            $mainCarCategory = $xhb->getCategory($xhb->getCarCategory());
            $carCategories = [
                $mainCarCategory->getKey() => $mainCarCategory
            ];
            $carCategories += $mainCarCategory
                ->getChildrenCategories()
                ->getItems();
            $this->_carCategories = $carCategories;
        }

        return $this->_carCategories;
    }

    /**
     *
     * @param $period \DatePeriod
     * @param $categoryIds int[]
     * @return array
     */
    public function getPeriodConsumptionSummaryData(\DatePeriod $period, $categoryIds = null) {
        $startDate = $period->start;
        $endDate = $period->end;
        $periodConsumptionSummaryData = [
            'per100' => [],
            'total'  => [
                'meter'       => 0,
                'fuel'        => 0,
                'fuel_cost'   => 0,
                'other_costs' => null,   //FIXME Not handled yet
                'total_cost'  => 0
            ]
        ];
        $found = false;
        foreach($this->getConsumptionData($categoryIds) as $cd) {
            if ($cd['operation']->getDateModel() < $startDate) {
                continue;
            }

            if ($cd['operation']->getDateModel() > $endDate) {
                break;
            }

            $found = true;
            $periodConsumptionSummaryData['total']['meter'] += $cd['dist'];
            $periodConsumptionSummaryData['total']['fuel'] += $cd['fuel'];
            $periodConsumptionSummaryData['total']['fuel_cost'] += $cd['amount'];
            //$periodConsumptionSummaryData['total']['other_costs'] += $cd['amount'];
            $periodConsumptionSummaryData['total']['total_cost'] += $cd['amount'];
        }

        if ($found) {
            foreach(array_keys($periodConsumptionSummaryData['total']) as $type) {
                $periodConsumptionSummaryData['per100'][$type] = round($periodConsumptionSummaryData['total'][$type]
                    / $periodConsumptionSummaryData['total']['meter'] * 100, 2);
                $periodConsumptionSummaryData['total'][$type] = round($periodConsumptionSummaryData['total'][$type], 2);
            }

            return $periodConsumptionSummaryData;
        }

        return false;
    }

    /**
     * @param $period \DatePeriod
     * @param $categoryIds int[]
     * @return array
     */
    public function getPeriodConsumptionData(\DatePeriod $period, $categoryIds = null): array {
        $startDate = $period->start;
        $endDate = $period->end;
        $periodConsumptionData = [];
        foreach($this->getConsumptionData($categoryIds) as $cd) {
            if ($cd['operation']->getDateModel() < $startDate) {
                continue;
            }

            if ($cd['operation']->getDateModel() > $endDate) {
                break;
            }

            $periodConsumptionData[] = $cd;
        }

        return $periodConsumptionData;
    }

    /**
     * @param $categoryIds int[] Categories to use as filter, NULL to use default car categories from XHB
     * @return array
     */
    public function getConsumptionData($categoryIds = null) {
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $carCategoryIds = $categoryIds === [] ? array_keys($this->getCarCategories()) : $categoryIds;
        sort($carCategoryIds);

        $cacheKey = implode('-', $carCategoryIds);
        if (!isset($this->_consumptionData[$cacheKey])) {
            $operationCollection = $this->getXhb()->getOperationCollection()
                ->addFieldToFilter(
                    'categories',
                    ['in' => $carCategoryIds]
                )
                ->orderBy('date', SORT_ASC);

            $consumptionData = [];
            $lastMeter = false;
            $volSinceLastFullRefuel = 0;
            $distSinceLastFullRefuel = 0;
            foreach($operationCollection as $op) {
                foreach($this->extractRefuelData($op) as $refuelData) {
                    $isPartial = $refuelData['op'] == '~';

                    if ($lastMeter !== false) {
                        $dist = $refuelData['dist'] - $lastMeter;
                        $distSinceLastFullRefuel += $dist;
                        $volSinceLastFullRefuel += $refuelData['volume'];

                        if ($isPartial) {
                            $per100 = false;
                            $ratio = false;
                        } else {
                            $per100 = round($volSinceLastFullRefuel * 100 / $distSinceLastFullRefuel, 2);
                            $ratio = round($distSinceLastFullRefuel / $volSinceLastFullRefuel, 2);

                            $distSinceLastFullRefuel = 0;
                            $volSinceLastFullRefuel = 0;
                        }
                    }
                    else {
                        $dist = false;
                        $per100 = false;
                        $ratio = false;
                    }

                    $consumptionData[] = [
                        'date'      => $op->getDateModel(),
                        'meter'     => $refuelData['dist'],
                        'fuel'      => $refuelData['volume'],
                        'price'     => abs(round($refuelData['amount'] / $refuelData['volume'], 3)),
                        'amount'    => $refuelData['amount'],
                        'dist'      => $dist,
                        'per-100'   => $per100,
                        'ratio'     => $ratio,
                        'operation' => $op,
                        'category'  => $refuelData['category']
                    ];
                    $lastMeter = $refuelData['dist'];
                }
            }

            $this->_consumptionData[$cacheKey] =& $consumptionData;
        }

        return $this->_consumptionData[$cacheKey];
    }

    /**
     * @return list<non-empty-array<('amount' | 'category' | 'dist' | 'op' | 'volume'), mixed>>
     */
    public function extractRefuelData(Operation $operation): array {
        $consumptionData = [];
        $rawData = array_merge([
                [
                    'category' => $operation->getCategory(),
                    'amount' => $operation->getAmount(),
                    'wording' => $operation->getWording()
                ]
            ],
            $operation->getSplitAmount()
        );

        $keys = ['dist', 'volume', 'op'];
        foreach($rawData as $rawDatum) {
            if (preg_match_all(self::CONSUMPTION_WORDING_PATTERN, $rawDatum['wording'], $matches, PREG_SET_ORDER)) {
                $refuelData = [];
                foreach($matches as $m) {
                    foreach($keys as $k) {
                        if (isset($m[$k]) && $m[$k]) {
                            $refuelData[$k] = $m[$k];
                        }
                    }
                }

                // Check if we have the three necessary info: dist, volume and operator
                if (count($refuelData) == 3) {
                    $refuelData['category'] = $rawDatum['category'];
                    $refuelData['amount']   = $rawDatum['amount'];
                    $consumptionData[] = $refuelData;
                }
            }
        }

        return $consumptionData;
    }

    /**
     * @return array{date: mixed, distance: (float | int)}[]
     */
    public function getDistanceTraveledByPeriod(\DatePeriod $period, $categoryIds = null): array {
        $startDate = $period->getStartDate();
        $endDate = $period->getEndDate();
        $interval = $period->getDateInterval();

        $consumptionData = $this->getConsumptionData($categoryIds);
        $distanceByPeriod = [];
        $nonNullPeriodFound = false;

        // Find first operation after start date
        while($cd = next($consumptionData)) {
            if ($cd['operation']->getDateModel() >= $startDate) {
                break;
            }
        }

        // Sum traveled distances for each period
        foreach ($period as $date) {
            $currentPeriodDistance = 0;
            do {
                if ($cd) {
                    if ($cd['operation']->getDateModel() < $date) {
                        $nonNullPeriodFound = true;
                        $currentPeriodDistance += $cd['dist'];
                    }
                    else {
                        $distanceByPeriod[] = [
                            'date' => $date,
                            'distance' => $currentPeriodDistance
                        ];
                        continue 2;
                    }
                }
            } while ($cd = next($consumptionData));
        }

        if ($nonNullPeriodFound) {
            return $distanceByPeriod;
        }

        return [];
    }
}
