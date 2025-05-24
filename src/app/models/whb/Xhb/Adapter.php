<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 27/06/16
 * Time: 18:31
 */

namespace app\models\whb\Xhb;


use app\models\core\Main;
use NinjaMutex\Lock\FlockLock;
use Xhb\Adapter\AdapterInterface as XhbAdapterInterface;
use Xhb\Adapter\Factory as XhbAdapterFactory;
use Xhb\Parser as XhbParser;

class Adapter
{
    public $_resourceAdater;

    /**
     * @var array
     */
    protected $_config;

    /**
     * @var array
     */
    protected $_hive;

    /**
     * @var XhbParser
     */
    protected $_parser;

    /**
     * @var string
     */
    protected $_xhbId;

    /**
     * @var XhbAdapterInterface
     */
    protected $_resourceAdapter;

    /**
     * @param $fw \Base
     * @param $xhbFile string
     * @param $config array
     * @param \Base $fw
     * @param string $xhbFile
     */
    public function __construct(protected $_fw, protected $_xhbFile, protected array $_rawConfig)
    {
    }

    public function getParser() {
        if (!$this->_parser) {
            $this->_parser = new XhbParser($this->_xhbFile);
        }

        return $this->_parser;
    }

    public function getXhbId() {
        if (!$this->_xhbId) {
            $this->_xhbId = $this->_generateXhbId($this->getParser()->getUniqueKey());
        }

        return $this->_xhbId;
    }

    protected function _getConfig() {
        if (!$this->_config) {
            $hive = $this->_fw->hive();
            $hive['xhbid'] = $this->getXhbId();
            $this->_processConfig($hive);
        }

        return $this->_config;
    }

    protected function _generateXhbId(string $xhbUniqueKey): string {
        return sha1(Main::app()->getConfig('VERSION') . $xhbUniqueKey);
    }

    public function isXhbLoaded() {
        return $this->getResourceAdapter()->xhbExists($this->getXhbId());
    }

    public function loadXhb($force = false) {
        $xhbId = $this->getXhbId();
        $flock = new FlockLock($this->_fw->get('TEMP'));
        if ($force || !$this->isXhbLoaded()) {
            if ($flock->acquireLock($xhbId)) {
                $this->getResourceAdapter()->importXhbData(
                    $this->_parser->getXhbData(),
                    $xhbId
                );
                $flock->releaseLock($xhbId);
            }
            else {
                while ($flock->isLocked()) {
                    sleep(1);
                }
            }
        }

        $xhb = new \Xhb\Model\Xhb($this->_getConfig());
        return $xhb->load($this->getXhbId());
    }

    /**
     * @return AdapterInterface
     */
    public function getResourceAdapter() {
        if (!$this->_resourceAdater) {
            $this->_resourceAdapter = XhbAdapterFactory::create($this->_getConfig());
        }

        return $this->_resourceAdapter;
    }

    protected function _processConfig($hive) {
        $this->_config = $this->_rawConfig;
        array_walk_recursive($this->_config, [$this, '_filterConfig'], $hive);
    }

    protected function _filterConfig(&$val, $key, $hive) {
        $val = preg_replace_callback('/({)?@([a-z\._]+)(?(1)})/i', fn(array $var) => $hive[$var[2]] ?? $var, $val);
    }
}
