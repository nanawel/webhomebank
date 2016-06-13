<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 08/06/16
 * Time: 14:05
 */

namespace tests\xhb\models\Xhb;

use app\models\core\Main;
use app\models\whb\Session\Xhb as XhbSession;
use xhb\models\Operation;
use xhb\models\Xhb;
use xhb\models\Xhb\DateHelper;

class DateHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Xhb
     */
    protected $xhb;

    public function setUp() {
        $session = Main::app()->getSession('xhb');
        $this->xhb = $session->getModel();
    }

    public function testXhbGetDateHelper() {
        $fixture = $this->xhb->getDateHelper();
        $this->assertInstanceOf('\xhb\models\Xhb\DateHelper', $fixture);
    }

    public function testGetPredefinedTimePeriods_thisMonth() {
        $fixture = $this->xhb->getDateHelper(false);

        // THIS MONTH (current date)
        $thisMonth = array(
            'start' => $this->_getDateTime('first day of this month'),
            'end'   => $this->_getDateTime('last day of this month')
        );
        $periods = $fixture->getPredefinedTimePeriods();
        $this->assertEquals($thisMonth, $periods[DateHelper::TIME_PERIOD_THIS_MONTH]);
    }

    public function testGetPredefinedTimePeriods_thisMonthFixed() {
        $fixture = $this->xhb->getDateHelper(false);

        // THIS MONTH (fixed date)
        $fixture->setReferenceDate($this->_getDateTime('2016-02-15'));
        $thisMonth = array(
            'start' => $this->_getDateTime('2016-02-01'),
            'end'   => $this->_getDateTime('2016-02-29')
        );
        $periods = $fixture->getPredefinedTimePeriods();
        $this->assertEquals($thisMonth, $periods[DateHelper::TIME_PERIOD_THIS_MONTH]);
    }

    public function testGetPredefinedTimePeriods_thisQuarter() {
        $fixture = $this->xhb->getDateHelper(false);

        // THIS QUARTER (current date)
        $month = date('n');
        $firstMonthOfQuarter = $month > 9 ? 10 : $month > 6 ? 7 : $month > 3 ? 4 : 1;
        $lastMonthOfQuarter = $firstMonthOfQuarter + 2;
        $thisQuarter = array(
            'start' => $this->_getDateTime('first day of ' . \DateTime::createFromFormat('!m', $firstMonthOfQuarter)->format('F')),
            'end'   => $this->_getDateTime('last day of ' . \DateTime::createFromFormat('!m', $lastMonthOfQuarter)->format('F'))
        );
        $periods = $fixture->getPredefinedTimePeriods();
        $this->assertEquals($thisQuarter, $periods[DateHelper::TIME_PERIOD_THIS_QUARTER]);
    }

    public function testGetPredefinedTimePeriods_lastMonth() {
        $fixture = $this->xhb->getDateHelper(false);

        // LAST MONTH (fixed date)
        $fixture->setReferenceDate($this->_getDateTime('2016-01-15'));
        $thisQuarter = array(
            'start' => $this->_getDateTime('2015-12-01'),
            'end'   => $this->_getDateTime('2015-12-31')
        );
        $periods = $fixture->getPredefinedTimePeriods();
        $this->assertEquals($thisQuarter, $periods[DateHelper::TIME_PERIOD_LAST_MONTH]);
    }

    public function testGetPredefinedTimePeriods_lastQuarter() {
        $fixture = $this->xhb->getDateHelper(false);

        // LAST QUARTER (current date)
        $month = date('n');
        $firstMonthOfLastQuarter = $month > 9 ? 7 : $month > 6 ? 4 : $month > 3 ? 1 : 10;
        $lastMonthOfLastQuarter = $firstMonthOfLastQuarter + 2;
        $thisQuarter = array(
            'start' => $this->_getDateTime('first day of ' . \DateTime::createFromFormat('!m', $firstMonthOfLastQuarter)->format('F')),
            'end'   => $this->_getDateTime('last day of ' . \DateTime::createFromFormat('!m', $lastMonthOfLastQuarter)->format('F'))
        );
        $periods = $fixture->getPredefinedTimePeriods();
        $this->assertEquals($thisQuarter, $periods[DateHelper::TIME_PERIOD_LAST_QUARTER]);
    }

    public function testGetPredefinedTimePeriods_lastQuarterFixed() {
        $fixture = $this->xhb->getDateHelper(false);

        // LAST QUARTER (fixed at 2016-02-15)
        $fixture->setReferenceDate($this->_getDateTime('2016-02-15'));
        $thisQuarter = array(
            'start' => $this->_getDateTime('2015-10-01'),
            'end'   => $this->_getDateTime('2015-12-31')
        );
        $periods = $fixture->getPredefinedTimePeriods();
        $this->assertEquals($thisQuarter, $periods[DateHelper::TIME_PERIOD_LAST_QUARTER]);
    }

    public function testGetPredefinedTimePeriods_allDate() {
        $fixture = $this->xhb->getDateHelper(false);

        // ALL DATE
        $oldestOperationDate = $this->_getDateTime('now');
        $mostRecentOperationDate = $this->_getDateTime('1970-01-01');
        /* @var $op Operation */
        foreach ($this->xhb->getOperationCollection() as $op) {
            if ($op->getDateModel() < $oldestOperationDate) {
                echo $op->getDateModel()->format('Y-m-d') . "\n";
                $oldestOperationDate = $op->getDateModel();
            }
            if ($op->getDateModel() > $mostRecentOperationDate) {
                $mostRecentOperationDate = $op->getDateModel();
            }
        }
        $allDate = array(
            'start' => $oldestOperationDate,
            'end'   => $mostRecentOperationDate
        );
        $periods = $fixture->getPredefinedTimePeriods();
        $this->assertEquals($allDate, $periods[DateHelper::TIME_PERIOD_ALL_DATE]);
    }

    private function _getDateTime($strtotimeExpr, $tz = null) {
        if (!$tz) {
            $tz = $utcTZ = new \DateTimeZone('UTC');
        }
        $d = new \DateTime('now', $tz);
        $d->modify($strtotimeExpr);
        $d->setTime(0, 0, 0);
        return $d;
    }
}
 