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
use app\models\whb\Xhb\Adapter as XhbAdapter;
use DB\SQL;
use xhb\adapters\Db\Sqlite;
use xhb\adapters\Db;
use xhb\Parser;
use Zend\Db\Adapter\Adapter;

class Xhb extends Session
{
    protected $_model;

    /**
     * @return \xhb\models\Xhb
     */
    public function getModel() {
        if (!$this->_model) {
            if (!$xhbFile = $this->get('xhb_file')) {
                throw new \Exception('Missing XHB file');
            }
            $adapter = new XhbAdapter(\Base::instance(), $xhbFile, Main::app()->getConfig('XHB'));
            $this->_model = $adapter->loadXhb();
        }
        return $this->_model;
    }

    /**
     * @param $model \xhb\models\Xhb
     */
    public function setModel($model) {
        $this->_model = $model;
        return $this;
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