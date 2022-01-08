<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 08/11/15
 * Time: 14:41
 */

namespace Xhb\Model\Xhb;


use Xhb\Model\Operation;
use Xhb\Model\XhbModel;

class DateHelper extends XhbModel
{
    const TIME_PERIOD_THIS_MONTH    = 'this_month';
    const TIME_PERIOD_LAST_MONTH    = 'last_month';
    const TIME_PERIOD_THIS_QUARTER  = 'this_quarter';
    const TIME_PERIOD_LAST_QUARTER  = 'last_quarter';
    const TIME_PERIOD_THIS_YEAR     = 'this_year';
    const TIME_PERIOD_LAST_YEAR     = 'last_year';
    const TIME_PERIOD_LAST_30D      = 'last_30d';
    const TIME_PERIOD_LAST_60D      = 'last_60d';
    const TIME_PERIOD_LAST_90D      = 'last_90d';
    const TIME_PERIOD_LAST_12M      = 'last_12m';
    const TIME_PERIOD_ALL_DATE      = 'all_date';
    const TIME_PERIOD_DEFAULT       = self::TIME_PERIOD_THIS_MONTH;

    protected $_periods = null;

    /**
     *
     * @return array
     */
    public function getPredefinedTimePeriods() {
        if (!$this->_periods) {
            $this->_periods = array();
            $this->_periods[self::TIME_PERIOD_THIS_MONTH] = array(
                'start' => gmmktime(0, 0, 0, $this->date('m'), 1, $this->date('Y')),
                'end'   => gmmktime(0, 0, 0, $this->date('m') + 1, 0, $this->date('Y'))
            );

            $lastMonthYear = $this->date('m') > 1 ? $this->date('Y') : $this->date('Y') - 1;
            $this->_periods[self::TIME_PERIOD_LAST_MONTH] = array(
                'start' => gmmktime(0, 0, 0, ($this->date('m') + 10) % 12 + 1, 1, $lastMonthYear),
                'end'   => gmmktime(0, 0, 0, $this->date('m'), 0, $this->date('Y'))
            );

            $thisQuarter = ceil(($this->date('m') / 3));
            $firstMonthOfQuarter = $thisQuarter * 3 - 2;
            $this->_periods[self::TIME_PERIOD_THIS_QUARTER] = array(
                'start' => gmmktime(0, 0, 0, $firstMonthOfQuarter, 1, $this->date('Y')),
                'end'   => gmmktime(0, 0, 0, $firstMonthOfQuarter + 3, 0, $this->date('Y'))
            );

            $lastQuarter = ($thisQuarter + 6) % 4 + 1;
            $firstMonthOfLastQuarter = $lastQuarter * 3 - 2;
            $lastQuarterYear = $thisQuarter > 1 ? $this->date('Y') : $this->date('Y') - 1;
            $this->_periods[self::TIME_PERIOD_LAST_QUARTER] = array(
                'start' => gmmktime(0, 0, 0, $firstMonthOfLastQuarter, 1, $lastQuarterYear),
                'end'   => gmmktime(0, 0, 0, $firstMonthOfLastQuarter + 3, 0, $lastQuarterYear)
            );

            $this->_periods[self::TIME_PERIOD_THIS_YEAR] = array(
                'start' => gmmktime(0, 0, 0, 1, 1, $this->date('Y')),
                'end'   => gmmktime(0, 0, 0, 1, 0, $this->date('Y') + 1)
            );

            $this->_periods[self::TIME_PERIOD_LAST_YEAR] = array(
                'start' => gmmktime(0, 0, 0, 1, 1, $this->date('Y') - 1),
                'end'   => gmmktime(0, 0, 0, 1, 0, $this->date('Y'))
            );

            $lastDaysPeriods = array(
                self::TIME_PERIOD_LAST_30D,
                self::TIME_PERIOD_LAST_60D,
                self::TIME_PERIOD_LAST_90D,
                self::TIME_PERIOD_LAST_12M
            );
            foreach($lastDaysPeriods as $ldp) {
                $units = preg_replace('/[^0-9]/', '', $ldp);
                switch (substr($ldp, -1)) {
                    case 'd':
                    $this->_periods[$ldp] = array(
                        'start' => gmmktime(0, 0, 0, $this->date('m'), $this->date('d') - $units, $this->date('Y')),
                        'end'   => gmmktime(0, 0, 0, $this->date('m'), $this->date('d'), $this->date('Y'))
                    );
                    break;

                    case 'm':
                    $this->_periods[$ldp] = array(
                        'start' => gmmktime(0, 0, 0, $this->date('m') - $units, $this->date('d'), $this->date('Y')),
                        'end'   => gmmktime(0, 0, 0, $this->date('m'), $this->date('d'), $this->date('Y'))
                    );
                }
            }

            $operations = $this->getXhb()->getOperationCollection()->getItems();
            /* @var $firstItem Operation */
            $firstItem = current($operations);
            end($operations);
            /* @var $lastItem Operation */
            $lastItem = current($operations);
            $this->_periods[self::TIME_PERIOD_ALL_DATE] = array(
                'start' => $firstItem->getDateModel()->getTimestamp(),
                'end'   => $lastItem->getDateModel()->getTimestamp()
            );

            $utcTZ = new \DateTimeZone('UTC');
            foreach($this->_periods as &$p) {
                $p['start'] = new \DateTime('@' . $p['start'], $utcTZ);
                $p['end'] = new \DateTime('@' . $p['end'], $utcTZ);
            }
        }
        return $this->_periods;
    }

    /**
     * @param $periodConstant
     * @param string|\DateInterval $interval
     * @return \DatePeriod
     */
    public function getPeriodFromConstant($periodConstant, $interval = null) {
        $periods = $this->getPredefinedTimePeriods();
        if (isset($periods[$periodConstant])) {
            $periodDates = $periods[$periodConstant];
        } else {
            $periodDates = $periods[$this->TIME_PERIOD_DEFAULT];
        }
        if (is_string($interval)) {
            $interval = new \DateInterval($interval);
        }
        return $this->getDatePeriod($periodDates['start'], $periodDates['end'], $interval);
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return \DatePeriod
     */
    public static function getDatePeriod(\DateTime $startDate, \DateTime $endDate, \DateInterval $interval = null) {
        if ($interval === null) {
            $interval = $startDate->diff($endDate);
            $intervalDays = (int) $interval->format('%R%a');

            if ($intervalDays > 183) {                   // 6 months
                $interval = new \DateInterval('P' . round($intervalDays / 365) . 'M');    // scale = #days/365 months
            }
            elseif ($intervalDays > 31) {                // 1 month
                $interval = new \DateInterval('P1W');    // scale = 1 week
            }
            else {
                $interval = new \DateInterval('P1D');    // scale = 1 day
            }
        }
        $datePeriod = new \DatePeriod($startDate, $interval, $endDate);
        return $datePeriod;
    }

    /**
     * @param $format
     * @param null $timestamp
     * @return bool|string
     */
    public function date($format, $timestamp = null) {
        if ($timestamp === null) {
            if (! is_object($rd = $this->getReferenceDate())) {
                throw new \Exception('Invalid reference date');
            }
            $timestamp = $rd->getTimestamp();
        }
        return date($format, $timestamp);
    }

    public function setReferenceDate(\DateTime $referenceDate) {
        $this->setData('reference_date', $referenceDate);
        $this->_periods = null;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReferenceDate() {
        if (!$this->hasData('reference_date')) {
            $this->setReferenceDate(new \DateTime('today 00:00:00', new \DateTimeZone('UTC')));
        }
        return $this->getData('reference_date');
    }
} 