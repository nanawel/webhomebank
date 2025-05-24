<?php
namespace Xhb\Util;

/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 29/06/15
 * Time: 16:14
 */

class Date
{
    static $_dateZero = null;

    static $_utcTZ = null;

    /**
     * @param $julianDayNumber
     * @return \DateTime
     */
    public static function jdToDate(string $julianDayNumber) {
        $d = self::getDateZero();
        $d->add(new \DateInterval('P' . $julianDayNumber . 'D'));
        return $d;
    }

    /**
     * @param \DateTime|int|string $date
     * @return int
     * @throws \Exception
     */
    public static function dateToJd($date) {
        if (is_numeric($date)) {
            $date = new \DateTime('@' . $date, self::getUTCTimeZone());
        }

        if (is_string($date)) {
            $date = new \DateTime($date, self::getUTCTimeZone());
        }

        if ($date instanceof \DateTime) {
            $interval = $date->diff(self::getDateZero(), true);
            return $interval->days;
        }

        throw new \Exception('Invalid date value: ' . $date);
    }

    /**
     * @return \DateTime
     */
    protected static function getDateZero() {
        if (!self::$_dateZero) {
            self::$_dateZero = \DateTime::createFromFormat('Y-m-d H:i:s', '1-1-0 00:00:00', self::getUTCTimeZone());
        }

        return clone self::$_dateZero;
    }

    /**
     * @return \DateTimeZone
     */
    public static function getUTCTimeZone() {
        if (!self::$_utcTZ) {
            self::$_utcTZ = new \DateTimeZone('UTC');
        }

        return self::$_utcTZ;
    }

    /**
     * @param string|int $time
     * @return \DateTime
     */
    public static function getDate($time = 'now'): \DateTime {
        return new \DateTime($time, self::getUTCTimeZone());
    }
}