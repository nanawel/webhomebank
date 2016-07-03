<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 28/06/16
 * Time: 13:21
 */

namespace xhb\adapters\Db;


use xhb\adapters\Db;
use xhb\models\Resource\Db\Xhb;
use Zend\Db\Adapter\Adapter;

class Sqlite extends Db
{
//    public function xhbExists($xhbId)
//    {
//        return parent::xhbExists($xhbId);
//    }

    public function __construct(array $config) {
        parent::__construct($config);
        if (!isset($config['db']['database'])) {
            throw new \Exception('Missing database path');
        }
        $parentDir = dirname($config['db']['database']);
        if (!is_writable($parentDir)) {
            throw new \Exception($parentDir . ' is not writable');
        }
    }

    public function importXhbData($xhbData, $xhbId, $force = false) {
        try {
            if (parent::importXhbData($xhbData, $xhbId, $force)) {
                @chmod($this->_config['db']['database'], 0640);
            }
        }
        catch (\Exception $e) {
            // Destroy incomplete DB file
            @unlink($this->_config['db']['database']);
            throw $e;
        }
    }
}