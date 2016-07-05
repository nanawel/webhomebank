<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 27/06/16
 * Time: 18:31
 */

namespace app\models\whb\Xhb;


use app\models\core\Main;
use xhb\adapters\AdapterInterface as XhbAdapterInterface;
use xhb\adapters\Factory as XhbAdapterFactory;
use xhb\Parser as XhbParser;

class Adapter
{
    /**
     * @var \Base
     */
    protected $_fw;

    /**
     * @var string
     */
    protected $_xhbFile;

    /**
     * @var array
     */
    protected $_rawConfig;

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
     */
    public function __construct($fw, $xhbFile, array $config) {
        $this->_fw = $fw;
        $this->_xhbFile = $xhbFile;
        $this->_rawConfig = $config;
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

    protected function _generateXhbId($xhbUniqueKey) {
        return sha1(Main::app()->getConfig('VERSION') . $xhbUniqueKey);
    }

    public function isXhbLoaded() {
        return $this->getResourceAdapter()->xhbExists($this->getXhbId());
    }

    public function loadXhb($force = false) {
        if ($force || !$this->isXhbLoaded()) {
            $this->getResourceAdapter()->importXhbData(
                $this->_parser->getXhbData(),
                $this->getXhbId()
            );
        }
        $xhb = new \xhb\models\Xhb($this->_getConfig());
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
        array_walk_recursive($this->_config, array($this, '_filterConfig'), $hive);
    }

    protected function _filterConfig(&$val, $key, $hive) {
        $val = preg_replace_callback('/({)?@([a-z\._]+)(?(1)})/i', function ($var) use ($hive) {
            return isset($hive[$var[2]]) ? $hive[$var[2]] : $var;
        }, $val);
    }
}