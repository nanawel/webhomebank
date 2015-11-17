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
use xhb\models\Resource\Manager;
use xhb\Parser;
use Zend\Db\Adapter\Adapter;

class Xhb extends Session
{
    protected $_xhbFile = null;
    protected $_xhbModel = null;

    /**
     *
     * @return \xhb\models\Xhb
     */
    public function getModel() {
        if ($this->_xhbModel === null) {
            $this->_xhbModel = $this->_initXhbModel();
        }
        return $this->_xhbModel;
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

    public function _initXhbModel() {
        $parser = new Parser($this->get('xhb_file'));
        $xhbParams = $this->_getXhbConfig();
        $xhbId = $this->_generateXhbId($parser->getUniqueKey());

        $resourceParams = $xhbParams['RESOURCE_CONFIG'];
        switch ($resourceParams['TYPE']) {
            case 'db':
                if (isset($resourceParams['DB']['driver'])) {
                    switch($resourceParams['DB']['driver']) {
                        case 'Pdo_Sqlite':
                            $this->_importXhbToSqlite($xhbParams, $resourceParams, $parser, $xhbId);
                            break 2;

                        // TODO Handle other databases

                        default:
                            throw new \Exception('Unsupported driver "' . $resourceParams['DB']['driver'] . '"');
                    }
                    break;
                }
                else {
                    throw new \Exception('Missing driver for resource type "db" (try "Pdo_Sqlite")');
                }

            case null:
            case 'memory':
                throw new \Exception('"memory" resource type is deprecated, please use "db" instead.');
                $xhbParams['xhb_id'] = $xhbId;
                $xhbParams['xhb_data'] = $parser->getXhbData();
                break;

            default:
                throw new \Exception('Unsupported resource type "' . $resourceParams['TYPE'] . '"');
        }

        // Init resource manager
        Manager::instance()->setData($resourceParams, null, $xhbId);

        $xhb = new \xhb\models\Xhb($xhbParams);
        return $xhb->load($xhbId);
    }

    protected function _generateXhbId($xhbUniqueKey) {
        return sha1(Main::app()->getConfig('VERSION') . $xhbUniqueKey);
    }

    /**
     * @param $xhbParams
     * @return string Connection string to DB
     */
    protected function _importXhbToSqlite(&$xhbParams, &$resourceParams, Parser $parser, $xhbId) {
        $dbConfig =& $resourceParams['DB'];
        if (!isset($dbConfig['database']) || empty($dbConfig['database'])) {
            // Create a DB file per XHB file, using unique ID
            $dbFilename = 'xhb-' . $xhbId . '.sqlite';
            $dbPath = Main::app()->getTmpDir() . $dbFilename;
            $dbConfig['database'] = $dbPath;
        }
        $resourceParams['connection'] = $adapter = new Adapter($dbConfig);
        $resourceParams['sql'] = new \Zend\Db\Sql\Sql($adapter);
        $xhbAdapter = new Db($adapter);

        try {
            if (!$xhbAdapter->xhbExists($xhbId)) {
                if ($xhbAdapter->importXhbData($parser->getXhbData(), $xhbId)) {
                    @chmod($dbConfig['database'], 0640);
                    $this->addMessage(I18n::instance()->tr('XHB imported to database successfully!'), self::MESSAGE_INFO);
                }
            }
        }
        catch (\Exception $e) {
            // Destroy incomplete DB file
            unlink($dbConfig['database']);
            throw $e;
        }

        //TODO Clean up old db files
    }

    protected function _getXhbConfig($key = null) {
        return $key === null ? Main::app()->getConfig('XHB') : Main::app()->getConfig('XHB.' . $key);
    }
} 