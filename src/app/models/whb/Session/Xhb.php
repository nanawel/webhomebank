<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 08/07/15
 * Time: 07:16
 */

namespace app\models\whb\Session;

use app\models\core\I18n;
use app\models\core\Main;
use app\models\core\Session;
use DB\SQL;
use xhb\adapters\Db\Sqlite;
use xhb\adapters\Db;
use xhb\models\Resource\Manager as ResourceManager;
use xhb\Parser;
use Zend\Db\Adapter\Adapter;

class Xhb extends Session
{
    /**
     * Backward-compatibility method
     *
     * @return \xhb\models\Xhb
     */
    public function getModel() {
        return $this->get('model');
    }

    public function getCarDistanceUnit() {
        if ($unit = $this->get('car_distance_unit')) {
            return $unit;
        }
        return Main::app()->getConfig('CAR_DISTANCE_UNIT');
    }

    public function getCarFuelVolumeUnit() {
        if ($unit = $this->get('car_volume_unit')) {
            return $unit;
        }
        return Main::app()->getConfig('CAR_VOLUME_UNIT');
    }

    protected function _getXhbConfig($key = null) {
        return $key === null ? Main::app()->getConfig('XHB') : Main::app()->getConfig('XHB.' . $key);
    }

    public function getCurrencyCode() {
        if (!$this->get('currency_code')) {
            $this->set('currency_code', Main::app()->getConfig('CURRENCY'));
        }
        return $this->get('currency_code');
    }

    public function setCurrencyCode($code) {
        return $this->set('currency_code', $code);
    }
}